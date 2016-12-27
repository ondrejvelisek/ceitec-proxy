<?php

/**
 * Class sspmod_perun_Auth_Process_PerunIdentity
 *
 * This module connects to Perun and search for user by userExtSourceLogin. If the user does not exists in Perun
 * or he is not in group assigned to service provider it redirects him to configurable url (redirect property).
 * It adds callback query parameter (name of parameter is configurable by callbackParamName property)
 * where user can be redirected after successfull registration of his identity and try process again.
 * Also it adds 'vo' and 'group' query parameter to let registrar know where user should be registered.
 *
 * If user exists it fills 'perun' to request structure containing 'userId' and 'groups' fields.
 * User is not allowed to pass this filter until he registers and 'perun' structure is filled properly.
 *
 * It is supposed to be used in IdP context because it needs to know entityId of this idp from request.
 * Means it should be placed in idp-hosted metadata.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_PerunIdentity extends SimpleSAML_Auth_ProcessingFilter
{
	private $uidAttr;
	private $redirect;
	private $vo;
	private $callbackParamName;
	private $interface;

	/**
	 * @var sspmod_perun_Adapter
	 */
	private $adapter;


	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		if (!isset($config['uidAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: missing mandatory configuration option 'uidAttr'.");
		}
		if (!isset($config['redirect'])) {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: missing mandatory configuration option 'redirect'.");
		}
		if (!isset($config['vo'])) {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: missing mandatory configuration option 'vo'.");
		}
		if (!isset($config['callbackParamName'])) {
			$config['callbackParamName'] = 'targetnew';
		}
		if (!isset($config['interface'])) {
			$config['interface'] = sspmod_perun_Adapter::RPC;
		}

		$this->uidAttr = (string) $config['uidAttr'];
		$this->redirect = (string) $config['redirect'];
		$this->vo = (string) $config['vo'];
		$this->callbackParamName = (string) $config['callbackParamName'];
		$this->interface = (string) $config['interface'];
		$this->adapter = sspmod_perun_Adapter::getInstance($this->interface);
	}


	public function process(&$request)
	{
		assert('is_array($request)');

		if (isset($request['Attributes'][$this->uidAttr][0])) {
			$uid = $request['Attributes'][$this->uidAttr][0];
		} else {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: " .
				"missing mandatory attribute " . $this->uidAttr . " in request.");
		}

		if (isset($request['IdPMetadata']['entityid'])) {
			$idpEntityId = $request['IdPMetadata']['entityid'];
		} else {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: Cannot find entityID of hosted IDP. " .
				"hint: Do you have this filter in IdP context?");
		}

		if (isset($request['SPMetadata']['entityid'])) {
			$spEntityId = $request['SPMetadata']['entityid'];
		} else {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: Cannot find entityID of remote SP. " .
				"hint: Do you have this filter in IdP context?");
		}


		$spGroups = $this->adapter->getSpGroups($spEntityId, $this->vo);

		if (empty($spGroups)) {
			throw new SimpleSAML_Error_Exception('No Perun groups are assigned with SP entityID '.$spEntityId.'. ' .
				'Hint1: create facility in Perun with attribute entityID of your SP. ' .
				'Hint2: assign groups to resource of the facility in Perun. '
			);
		}


		$perunUser = $this->adapter->getPerunUser($idpEntityId, $uid);

		if ($perunUser === null) {
			SimpleSAML_Logger::info('Perun user with identity: '.$uid.' has NOT been found. He is being redirected to register.');
			$this->register($request, $this->redirect, $this->callbackParamName, $this->vo, $spGroups, $this->interface);
		}


		$memberGroups = $this->adapter->getMemberGroups($perunUser['id'], $this->vo);

		SimpleSAML_Logger::info('member groups: '.var_export($memberGroups, true));
		SimpleSAML_Logger::info('sp groups: '.var_export($spGroups, true));

		$groups = $this->intersectById($spGroups, $memberGroups);

		if (empty($groups)) {
			SimpleSAML_Logger::info('Perun user with identity: '.$uid.' has been found but SP do NOT have sufficient rights to get information about him. '.
				'User has to register to specific VO or Group. He is being redirected to register. ');
			$this->register($request, $this->redirect, $this->callbackParamName, $this->vo, $spGroups, $this->interface);
		}

		SimpleSAML_Logger::info('Perun user with identity: '.$uid.' has been found and SP has sufficient rights to get info about him. '.
				'UserId '.$perunUser['id'].' is being set to request');

		if (!isset($request['perun'])) {
			$request['perun'] = array();
		}

		$request['perun']['userId'] = $perunUser['id'];
		$request['perun']['groups'] = $groups;

	}


	/**
	 * Redirects user to register (or consolidate unkwnown identity) on external page (e.g. registrar).
	 * If has more options to which group he can register offers him a list before redirection.
	 *
	 * @param string $request
	 * @param string $redirect url to external page where user can register
	 * @param string $callbackParamName name of query parameter where whould be stored url where external page can send user back to try authenticates again
	 * @param string $voShortName
	 * @param array $groups
	 * @param string $interface which interface should be used for connecting to Perun
	 */
	protected function register($request, $redirect, $callbackParamName, $voShortName, $groups, $interface) {

		$request['interface'] = $this->interface;
		$request['uidAttr'] = $this->uidAttr;
		$request['vo'] = $this->vo;
		$request['redirect'] = $this->redirect;
		$request['callbackParamName'] = $this->callbackParamName;

		$stateId  = SimpleSAML_Auth_State::saveState($request, 'perun:PerunIdentity');
		$callback = SimpleSAML_Module::getModuleURL('perun/perun_identity_callback.php', array('stateId' => $stateId));

		if ($this->containsProperty($groups, 'name', 'members')) {
			$this->registerDirectly($redirect, $callbackParamName, $callback, $voShortName);
		}
		if (sizeof($groups) === 1) {
			$this->registerDirectly($redirect, $callbackParamName, $callback, $voShortName, $groups[0]);
		} else {
			$this->registerChooseGroup($redirect, $callbackParamName, $callback, $voShortName, $groups, $interface);
		}

	}


	protected function registerDirectly($redirect, $callbackParamName, $callback, $voShortName, $group = null) {

		$params = array();
		$params['vo'] = $voShortName;
		if (!is_null($group)) {
			$params['group'] = $group['name'];
		}
		$params[$callbackParamName] = $callback;

		\SimpleSAML\Utils\HTTP::redirectTrustedURL($redirect, $params);

	}


	protected function registerChooseGroup($redirect, $callbackParamName, $callback, $voShortName, $groups, $interface) {

		$chooseGroupUrl = SimpleSAML_Module::getModuleURL('perun/perun_identity_choose_group.php');

		$groupNames = array();
		foreach ($groups as $group) {
			array_push($groupNames, $group['name']);
		}

		\SimpleSAML\Utils\HTTP::redirectTrustedURL($chooseGroupUrl, array(
			'redirect' => $redirect,
			'callback' => $callback,
			'callbackParamName' => $callbackParamName,
			'voShortName' => $voShortName,
			'groupNames' => $groupNames,
			'interface' => $interface,
		));

	}


	private function intersectById($spGroups, $memberGroups)
	{
		$intersection = array();
		foreach ($spGroups as $spGroup) {
			if ($this->containsProperty($memberGroups, 'id', $spGroup['id'])) {
				array_push($intersection, $spGroup);
			}
		}
		return $intersection;
	}


	private function containsProperty($entities, $name, $value)
	{
		foreach ($entities as $entity) {
			if ($entity[$name] === $value) {
				return true;
			}
		}
		return false;
	}


}
