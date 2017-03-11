<?php

# Check for OAuth errors
if(array_key_exists('error', $_REQUEST)) {
  if($_REQUEST['error'] == 'access_denied') {
    throw new SimpleSAML_Error_UserAborted();
  } else {
    throw new SimpleSAML_Error_Error($_REQUEST['error']);
  }
}

# Fetch the oauth code
if(!array_key_exists('code', $_REQUEST) || empty($_REQUEST['code'])) {
  throw new SimpleSAML_Error_BadRequest('Missing code parameter for Google OAuth callback endpoint.');
}
$code = $_REQUEST['code'];

# Fetch the authentication source state
if(!array_key_exists('state', $_REQUEST) || empty($_REQUEST['state'])) {
  throw new SimpleSAML_Error_BadRequest('Missing state parameter for Google OAuth callback endpoint.');
}
$stateId = $_REQUEST['state'];
$sid = SimpleSAML_Utilities::parseStateID($stateId);
$state = SimpleSAML_Auth_State::loadState($stateId, 'authgoogleoauth2:init');

# Fetch the instance of the authentication source
if(!array_key_exists('authgoogleoauth2:AuthID', $state)) {
  throw new SimpleSAML_Error_BadRequest('No state data for authgoogleoauth2:AuthID');
}
$sourceId = $state['authgoogleoauth2:AuthID'];
$source = SimpleSAML_Auth_Source::getById($sourceId);
if($source === NULL) {
  throw new SimpleSAML_Error_BadRequest('Could not find authentication source with id '.var_export($sourceId, TRUE));
}

$source->callback($code, $state);

SimpleSAML_Auth_Source::completeAuth($state);