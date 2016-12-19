<?php

/**
 * Class sspmod_perun_Auth_Process_FetchAttributes
 *
 * This filter fetchs user attributes by its names listed as keys of attrMap config property
 * and set them into values of attrMap property. Old values of attributes are replaced.
 *
 * It needs to know attribute name where is Perun user ID is stored. Use UnknownIdentity filter to obtain it.
 *
 * if perun attribute value is null or is not set at all SSP attribute is set to empty array.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_FetchAttributes extends SimpleSAML_Auth_ProcessingFilter
{
	private $perunUidAttr;
	private $attrMap;


	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		if (!isset($config['perunUidAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:FetchAttributes: missing mandatory configuration option 'perunUidAttr'.");
		}
		if (!isset($config['attrMap'])) {
			throw new SimpleSAML_Error_Exception("perun:FetchAttributes: missing mandatory configuration option 'attrMap'.");
		}
		$this->perunUidAttr = (string) $config['perunUidAttr'];
		$this->attrMap = (array) $config['attrMap'];
	}


	public function process(&$request)
	{
		assert('is_array($request)');

		if (isset($request['Attributes'][$this->perunUidAttr][0])) {
			$perunUid = $request['Attributes'][$this->perunUidAttr][0];
		} else {
			throw new SimpleSAML_Error_Exception("perun:FetchAttributes: " .
					"missing mandatory attribute '" . $this->perunUidAttr . "' in request.");
		}


		$perunAttrs = sspmod_perun_Rpc::get('attributesManager', 'getAttributes', array(
			'user' => $perunUid,
			'attrNames' => array_keys($this->attrMap),
		));


		foreach ($perunAttrs as $perunAttr) {

			$perunAttrName = $perunAttr['namespace'] . ":" . $perunAttr['friendlyName'];
			$sspAttr = $this->attrMap[$perunAttrName];

			if (is_null($perunAttr['value'])) {
				$value = array();
			} else if (is_array($perunAttr['value'])) {
				$value = $perunAttr['value'];
			} else {
				$value = array($perunAttr['value']);
			}

			SimpleSAML_Logger::debug("perun:FetchAttributes: perun attribute $perunAttrName was fetched. " .
					"Value ".implode(",", $value)." is being setted to ssp attribute $sspAttr");

			$request['Attributes'][$sspAttr] = $value;
		}

	}

}
