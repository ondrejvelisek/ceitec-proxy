<?php
/**
 * Created by IntelliJ IDEA.
 * User: ryanjgeyer
 * Date: 4/10/15
 * Time: 12:27 PM
 */

$include_file = dirname(dirname(dirname(dirname(__FILE__))))."/vendor/autoload.php";

require_once($include_file);

class sspmod_authgoogleoauth2_Auth_Source_authgoogleoauth2 extends SimpleSAML_Auth_Source {

  private $google_client;

  public function __construct($info, $config) {
    assert('is_array($info)');
    assert('is_array($config)');

    parent::__construct($info, $config);

    $cfgParse = SimpleSAML_Configuration::loadFromArray($config, 'Authentication source ' . var_export($this->authId, TRUE));

    $redirect_url = SimpleSAML_Module::getModuleURL('authgoogleoauth2/callback.php');

    SimpleSAML_Logger::info(sprintf('[authgoogleoauth2] Initialized OAuth2 client with redirect URL - %s', $redirect_url));

    $this->google_client = new Google_Client(array('user_objects' => true));
    $this->google_client->setClientId($cfgParse->getString('client_id'));
    $this->google_client->setClientSecret($cfgParse->getString('client_secret'));
    $this->google_client->setRedirectUri($redirect_url);
    $this->google_client->setDeveloperKey($cfgParse->getString('developer_key'));
    $this->google_client->setScopes(
      array(
        'openid',
	    'profile',
        'email'
      )
    );
    $this->google_client->setAccessType('online');
    $this->google_client->setApprovalPrompt('auto');
  }

  /**
   * Process a request.
   *
   * If an authentication source returns from this function, it is assumed to have
   * authenticated the user, and should have set elements in $state with the attributes
   * of the user.
   *
   * If the authentication process requires additional steps which make it impossible to
   * complete before returning from this function, the authentication source should
   * save the state, and at a later stage, load the state, update it with the authentication
   * information about the user, and call completeAuth with the state array.
   *
   * @param array &$state Information about the current authentication.
   */
  public function authenticate(&$state)
  {
    assert('is_array($state)');

    $state['authgoogleoauth2:AuthID'] = $this->authId;
    $stateId = SimpleSAML_Auth_State::saveState($state, 'authgoogleoauth2:init');
    $this->google_client->setState($stateId);

    $url = $this->google_client->createAuthUrl();
    SimpleSAML_Logger::info(sprintf('[authgoogleoauth2] Redirecting end user to Google OAuth - %s', $url));
    SimpleSAML_Utilities::redirectTrustedURL($url);
  }

  /**
   * Called from www/callback.php once the user has authenticated with Google OAauth and has been
   * redirected back to this auth source.
   *
   * @param $code The authorization code returned from the initial request @see https://developers.google.com/identity/protocols/OAuth2WebServer#handlingtheresponse
   * @param array &$state Information about the current authentication.
   */
  public function callback($code, array &$state) {
    $this->google_client->authenticate($code);
    $userinfo_service = new Google_Service_Oauth2($this->google_client);
    $userinfo = $userinfo_service->userinfo->get();
    $attributes = (array)$userinfo->toSimpleObject();
    $exported_attributes = array();
    foreach($attributes as $key => $val) {
      $exported_attributes[$key] = array($val);
    }
    $state['Attributes'] = $exported_attributes;
  }
}