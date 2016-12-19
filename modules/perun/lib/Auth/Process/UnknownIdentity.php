<?php

/**
 * Class sspmod_perun_Auth_Process_UnknownIdentity
 *
 * This module connects to Perun RPC and search for user by userExtSourceLogin. If the user does not exists in Perun
 * it redirects him to configurable url (redirect property).
 * It adds 'callback' query parameter where user can be redirected and try process again. e.g. user register himself.
 * If user exists it fills attribute defined by perunUidAttr with perun user id and control is passed to the next filter.
 *
 *
 * It relies on perun rpc. Configure it properly.
 *
 * It is supposed to be used in IdP context ecause it needs to know entity id of this idp from request.
 * Means it should be placed in idp-hosted metadata.
 *
 * most of work is done in unknown_identity_callback.php because when user comes back we want to process check again.
 * So this module does not allow unregistered users to continue.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_UnknownIdentity extends SimpleSAML_Auth_ProcessingFilter
{
	private $uidAttr;
	private $redirect;
	private $perunUidAttr;


	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		if (!isset($config['uidAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:UnknownIdentity: missing mandatory configuration option 'uidAttr'.");
		}
		if (!isset($config['redirect'])) {
			throw new SimpleSAML_Error_Exception("perun:UnknownIdentity: missing mandatory configuration option 'redirect'.");
		}
		if (!isset($config['perunUidAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:UnknownIdentity: missing mandatory configuration option 'perunUidAttr'.");
		}

		$this->uidAttr = (string) $config['uidAttr'];
		$this->redirect = (string) $config['redirect'];
		$this->perunUidAttr = (string) $config['perunUidAttr'];
	}


	public function process(&$request)
	{
		assert('is_array($request)');

		$request['uidAttr']  = $this->uidAttr;
		$request['redirect'] = $this->redirect;
		$request['perunUidAttr'] = $this->perunUidAttr;
		$stateId  = SimpleSAML_Auth_State::saveState($request, 'perun:UnknownIdentity');
		$url = SimpleSAML_Module::getModuleURL('perun/unknown_identity_callback.php');
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('stateId' => $stateId));

	}


}
