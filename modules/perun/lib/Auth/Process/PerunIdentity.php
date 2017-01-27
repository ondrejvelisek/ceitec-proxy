<?php

/**
 * Class sspmod_perun_Auth_Process_PerunIdentity
 *
 * This module connects to Perun and search for user by userExtSourceLogin. If the user does not exists in Perun
 * or he is not in group assigned to service provider it redirects him to configurable url (registerUrl property).
 * It adds callback query parameter (name of parameter is configurable by callbackParamName property)
 * where user can be redirected after successfull registration of his identity and try process again.
 * Also it adds 'vo' and 'group' query parameter to let registrar know where user should be registered.
 *
 * If user exists it fills 'perun' to request structure containing 'userId' and 'groups' fields.
 * User is not allowed to pass this filter until he registers and is in proper group and 'perun' structure is filled properly.
 *
 * It is supposed to be used in IdP context because it needs to know entityId of destination SP from request.
 * Means it should be placed e.g. in idp-hosted metadata.
 *
 * It relays on RetainIdPEntityID filter. Config it properly before this filter. (in SP context)
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_PerunIdentity extends SimpleSAML_Auth_ProcessingFilter
{
	const UID_ATTR = 'uidAttr';
	const VO_SHORTNAME = 'voShortName';
	const REGISTER_URL = 'registerUrl';
	const CALLBACK_PARAM_NAME = 'callbackParamName';
	const INTERFACE_PROPNAME = 'interface';
	const SOURCE_IDP_ENTITY_ID_ATTR = 'sourceIdPEntityIDAttr';

	private $uidAttr;
	private $registerUrl;
	private $voShortName;
	private $callbackParamName;
	private $interface;
	private $sourceIdPEntityIDAttr;

	/**
	 * @var sspmod_perun_Adapter
	 */
	private $adapter;


	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		if (!isset($config[self::UID_ATTR])) {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: missing mandatory config option '".self::UID_ATTR."'.");
		}
		if (!isset($config[self::REGISTER_URL])) {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: missing mandatory config option '".self::REGISTER_URL."'.");
		}
		if (!isset($config[self::VO_SHORTNAME])) {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: missing mandatory config option '".self::VO_SHORTNAME."'.");
		}
		if (!isset($config[self::CALLBACK_PARAM_NAME])) {
			$config[self::CALLBACK_PARAM_NAME] = 'targetnew';
		}
		if (!isset($config[self::INTERFACE_PROPNAME])) {
			$config[self::INTERFACE_PROPNAME] = sspmod_perun_Adapter::RPC;
		}
		if (!isset($config[self::SOURCE_IDP_ENTITY_ID_ATTR])) {
			$config[self::SOURCE_IDP_ENTITY_ID_ATTR] = sspmod_perun_Auth_Process_RetainIdPEntityID::DEFAULT_ATTR_NAME;
		}

		$this->uidAttr = (string) $config[self::UID_ATTR];
		$this->registerUrl = (string) $config[self::REGISTER_URL];
		$this->voShortName = (string) $config[self::VO_SHORTNAME];
		$this->callbackParamName = (string) $config[self::CALLBACK_PARAM_NAME];
		$this->interface = (string) $config[self::INTERFACE_PROPNAME];
		$this->sourceIdPEntityIDAttr = $config[self::SOURCE_IDP_ENTITY_ID_ATTR];
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

		if (isset($request['Attributes'][$this->sourceIdPEntityIDAttr][0])) {
			$idpEntityId = $request['Attributes'][$this->sourceIdPEntityIDAttr][0];
		} else {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: Cannot find entityID of source IDP. " .
				"hint: Did you properly configured RetainIdPEntityID filter in SP context?");
		}

		if (isset($request['SPMetadata']['entityid'])) {
			$spEntityId = $request['SPMetadata']['entityid'];
		} else {
			throw new SimpleSAML_Error_Exception("perun:PerunIdentity: Cannot find entityID of remote SP. " .
				"hint: Do you have this filter in IdP context?");
		}

		# SP can have its own register URL
		if (isset($request['SPMetadata'][self::REGISTER_URL])) {
			$this->registerUrl = $request['SPMetadata'][self::REGISTER_URL];
		}
		
		# SP can have its own associated voShortName
		if (isset($request['SPMetadata'][self::VO_SHORTNAME])) {
			$this->voShortName = $request['SPMetadata'][self::VO_SHORTNAME];
		}

		$vo = $this->adapter->getVoByShortName($this->voShortName);

		$spGroups = $this->adapter->getSpGroups($spEntityId, $vo);

		if (empty($spGroups)) {
			throw new SimpleSAML_Error_Exception(
				'No Perun groups in VO '.$vo->getName().'are assigned with SP entityID '.$spEntityId.'. ' .
				'Hint1: create facility in Perun with attribute entityID of your SP. ' .
				'Hint2: assign groups in VO '.$vo->getName().' to resource of the facility in Perun. '
			);
		}

		SimpleSAML_Logger::debug("SP GROUPs - ".var_export($spGroups, true));

		$user = $this->adapter->getPerunUser($idpEntityId, $uid);

		if ($user === null) {
			SimpleSAML_Logger::info('Perun user with identity: '.$uid.' has NOT been found. He is being redirected to register.');
			$this->register($request, $this->registerUrl, $this->callbackParamName, $vo, $spGroups, $this->interface);
		}


		$memberGroups = $this->adapter->getMemberGroups($user, $vo);

		SimpleSAML_Logger::info('member groups: '.var_export($memberGroups, true));
		SimpleSAML_Logger::info('sp groups: '.var_export($spGroups, true));

		$groups = $this->intersectById($spGroups, $memberGroups);

		if (empty($groups)) {
			SimpleSAML_Logger::info('Perun user with identity: '.$uid.' has been found but SP does NOT have sufficient rights to get information about him. '.
				'User has to register to specific VO or Group. He is being redirected to register. ');
			$this->register($request, $this->registerUrl, $this->callbackParamName, $vo, $spGroups, $this->interface);
		}

		SimpleSAML_Logger::info('Perun user with identity: '.$uid.' has been found and SP has sufficient rights to get info about him. '.
				'User '.$user->getName().' with id: '.$user->getId().' is being set to request');

		if (!isset($request['perun'])) {
			$request['perun'] = array();
		}

		$request['perun']['user'] = $user;
		$request['perun']['groups'] = $groups;

	}


	/**
	 * Redirects user to register (or consolidate unknown identity) on external page (e.g. registrar).
	 * If has more options to which group he can register offers him a list before redirection.
	 *
	 * @param string $request
	 * @param string $registerUrl url to external page where user can register
	 * @param string $callbackParamName name of query parameter where would be stored url where external page can send user back to try authenticates again
	 * @param sspmod_perun_model_Vo $vo
	 * @param sspmod_perun_model_Group[] $groups
	 * @param string $interface which interface should be used for connecting to Perun
	 */
	protected function register($request, $registerUrl, $callbackParamName, $vo, $groups, $interface) {

		$request['config'] = array(
			self::UID_ATTR => $this->uidAttr,
			self::VO_SHORTNAME => $this->voShortName,
			self::REGISTER_URL => $this->registerUrl,
			self::CALLBACK_PARAM_NAME => $this->callbackParamName,
			self::INTERFACE_PROPNAME => $this->interface,
			self::SOURCE_IDP_ENTITY_ID_ATTR => $this->sourceIdPEntityIDAttr,
		);

		$stateId  = SimpleSAML_Auth_State::saveState($request, 'perun:PerunIdentity');
		$callback = SimpleSAML_Module::getModuleURL('perun/perun_identity_callback.php', array('stateId' => $stateId));

		if ($this->containsGroupWithName($groups, 'members')) {
			$this->registerDirectly($registerUrl, $callbackParamName, $callback, $vo);
		}
		if (sizeof($groups) === 1) {
			$this->registerDirectly($registerUrl, $callbackParamName, $callback, $vo, $groups[0]);
		} else {
			$this->registerChooseGroup($registerUrl, $callbackParamName, $callback, $vo, $groups, $interface);
		}

	}

	/**
	 * @param string $registerUrl
	 * @param string $callbackParamName
	 * @param string $callback
	 * @param sspmod_perun_model_Vo $vo
	 * @param sspmod_perun_model_Group|null $group
	 */
	protected function registerDirectly($registerUrl, $callbackParamName, $callback, $vo, $group = null) {

		$params = array();
		$params['vo'] = $vo->getShortName();
		if (!is_null($group)) {
			$params['group'] = $group->getName();
		}
		$params[$callbackParamName] = $callback;

		\SimpleSAML\Utils\HTTP::redirectTrustedURL($registerUrl, $params);

	}

	/**
	 * @param string $registerUrl
	 * @param string $callbackParamName
	 * @param string $callback
	 * @param sspmod_perun_model_Vo $vo
	 * @param sspmod_perun_model_Group[] $groups
	 * @param string $interface
	 */
	protected function registerChooseGroup($registerUrl, $callbackParamName, $callback, $vo, $groups, $interface) {

		$chooseGroupUrl = SimpleSAML_Module::getModuleURL('perun/perun_identity_choose_group.php');

		$groupNames = array();
		foreach ($groups as $group) {
			array_push($groupNames, $group->getName());
		}

		\SimpleSAML\Utils\HTTP::redirectTrustedURL($chooseGroupUrl, array(
			self::REGISTER_URL => $registerUrl,
			self::CALLBACK_PARAM_NAME => $callbackParamName,
			self::VO_SHORTNAME => $vo->getShortName(),
			self::INTERFACE_PROPNAME => $interface,
			'groupNames' => $groupNames,
			'callbackUrl' => $callback,
		));

	}




	/**
	 * @param sspmod_perun_model_HasId[] $spGroups
	 * @param sspmod_perun_model_HasId[] $memberGroups
	 * @return sspmod_perun_model_HasId[]
	 */
	private function intersectById($spGroups, $memberGroups)
	{
		$intersection = array();
		foreach ($spGroups as $spGroup) {
			if ($this->containsId($memberGroups, $spGroup->getId())) {
				array_push($intersection, $spGroup);
			}
		}
		return $intersection;
	}

	/**
	 * @param sspmod_perun_model_HasId[] $entities
	 * @param int $value
	 * @return bool
	 */
	private function containsId($entities, $value)
	{
		foreach ($entities as $entity) {
			if ($entity->getId() === $value) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param sspmod_perun_model_Group[] $entities
	 * @param string $name
	 * @return bool
	 */
	private function containsGroupWithName($entities, $name)
	{
		foreach ($entities as $entity) {
			if ($entity->getName() === $name) {
				return true;
			}
		}
		return false;
	}


}
