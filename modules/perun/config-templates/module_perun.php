<?php

/**
 * This is example configuration of SimpleSAMLphp Perun interface.
 * Copy this file to default config directory and edit the properties.
 *
 * copy command (from SimpleSAML base dir)
 * cp modules/perun/module_perun.php config/
 */
$config = array(

	/**
	 * base url to rpc with slash at the end.
	 */
	'rpc_url' => 'https://perun.inside.cz/krb/rpc/',

	/**
	 * shortName of virtual organization in Perun this ProxyIdP is working with.
	 * note: Proxy IdP can work with only one VO.
	 */
	'vo' => 'voTest',

	/**
	 * username if rpc url is protected with basic auth.
	 */
	'username' => '_proxy-idp',

	/**
	 * password if rpc url is protected with basic auth.
	 */
	'password' => 'S-Perunem-na-vecne-casy-a-nikdy-jinak!'

);