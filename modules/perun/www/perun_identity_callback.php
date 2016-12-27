<?php
/**
 * See sspmod_perun_Auth_Process_PerunIdentity for mor information.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

$state = SimpleSAML_Auth_State::loadState($_REQUEST['stateId'], 'perun:PerunIdentity');

$config = array(
	'uidAttr' => $state['uidAttr'],
	'redirect' => $state['redirect'],
	'callbackParamName' => $state['callbackParamName'],
	'interface' => $state['interface'],
);

$perunIdentity = new sspmod_perun_Auth_Process_PerunIdentity($config, null);

// If this return it means it successfully get and fill perun identity.
$perunIdentity->process($state);

SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);

