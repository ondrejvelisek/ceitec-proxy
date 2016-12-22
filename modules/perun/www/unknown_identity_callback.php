<?php
/**
 * See sspmod_perun_Auth_Process_UnknownIdentity for mor information.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

$state = SimpleSAML_Auth_State::loadState($_REQUEST['stateId'], 'perun:UnknownIdentity');


if (isset($state['Attributes'][$state['uidAttr']][0])) {
	$uid = $state['Attributes'][$state['uidAttr']][0];
} else {
	throw new SimpleSAML_Error_Exception("perun:UnknownIdentity: " .
			"missing mandatory attribute " . $state['uidAttr'] . " in request.");
}

if (isset($state['IdPMetadata']['entityid'])) {
	$entityId = $state['IdPMetadata']['entityid'];
} else {
	throw new SimpleSAML_Error_Exception("perun:UnknownIdentity: Cannot find entityID of hosted IDP. " .
			"hint: Do you have this filter in IdP context?");
}


try {

	$user = sspmod_perun_Rpc::get('usersManager', 'getUserByExtSourceNameAndExtLogin', array(
		'extSourceName' => $entityId,
		'extLogin' => $uid,
	));

	SimpleSAML_Logger::info('Identity ' . $uid . ' has been found in Perun. ' .
			'User id: ' . $user['id'] . ', name: ' . $user['firstName'].' '.$user['lastName'] . '. Continuing in process');

	$state['Attributes'][$state['perunUidAttr']] = array($user['id']);
	SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);

} catch (sspmod_perun_Exception $e) {
	if ($e->getName() == "UserExtSourceNotExistsException") {

		$stateId  = SimpleSAML_Auth_State::saveState($state, 'perun:UnknownIdentity');
		$callback = SimpleSAML_Module::getModuleURL('perun/unknown_identity_callback.php', array('stateId' => $stateId));

		SimpleSAML_Logger::info('Unknown identity ' . $uid . '. Redirecting to ' . $state['redirect']);
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($state['redirect'], array($state['callbackParamName'] => $callback));

	} else {
		throw $e;
	}
}


// Just in case. Defensive programming. User should not ends here!
throw new SimpleSAML_Error_Exception('perun.UnknownIdentity: Fatal error.');

