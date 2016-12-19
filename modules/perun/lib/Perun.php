<?php

/**
 * Class sspmod_elixir_Perun
 *
 * Provides interface to call Perun RPC.
 * Configuration file 'module_perun.php' should be placed in in default config folder of SimpleSAMLphp.
 * For example of config file look at module_perun.php in perun (this) module directory.
 *
 * Example Usage:
 * ```php
 * try {
 *		$attribute = sspmod_perun_Rpc::get('attributesManager', 'getAttribute', array(
 *			'user' => $userId,
 *			'attributeName' => $attrName,
 * 		));
 * 		...
 * } catch (Perun_Exception $pe) {
 *		...
 * }
 * ```
 *
 */
class sspmod_perun_Rpc
{
	const CONFIG_FILE_NAME = 'module_perun.php';
	const PROPNAME_URL  = 'rpc_url';
	const PROPNAME_USER = 'username';
	const PROPNAME_PASS = 'password';


	public static function get($manager, $method, $params) {

		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$rpc_url = $conf->getString(self::PROPNAME_URL);
		$user = $conf->getString(self::PROPNAME_USER);
		$pass = $conf->getString(self::PROPNAME_PASS);

		$ch = curl_init();

		$uri = $rpc_url .'json/'.  $manager .'/'. $method;
		curl_setopt($ch, CURLOPT_URL, $uri .'?'. http_build_query($params));
		curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$json = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($json, true);

		if (isset($result['errorId'])) {
			self::error($result['errorId'], $result['name'], $result['message'], $uri, http_build_query($params));
		}

		return $result;
	}


	public static function post($manager, $method, $params) {
		$paramsJson = json_encode($params);

		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$rpc_url = $conf->getString(self::PROPNAME_URL);
		$user = $conf->getString(self::PROPNAME_USER);
		$pass = $conf->getString(self::PROPNAME_PASS);

		$ch = curl_init();

		$uri = $rpc_url .'json/'.  $manager .'/'. $method;
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsJson);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER,
			array('Content-Type:application/json',
				'Content-Length: ' . strlen($paramsJson))
		);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$json = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($json, true);

		if (isset($result['errorId'])) {
			self::error($result['errorId'], $result['name'], $result['message'], $uri, $paramsJson);
		}

		return $result;
	}


	private static function error($id, $name, $message, $uri, $params) {
		throw new Perun_Exception($id, $name, $message . "\ncall: $uri, params: " . $params);
	}

}


