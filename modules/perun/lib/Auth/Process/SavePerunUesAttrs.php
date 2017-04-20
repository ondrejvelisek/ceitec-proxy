<?php

/**
 * Class sspmod_perun_Auth_Process_SavePerunUesAttrs
 * This filter saves defined SAML2 attributes from request to Perun as UserExtSource attributes.
 * Attributes has to be created in Perun in advance and has to be of type List of strings.
 * It replaces old existing values.
 *
 * This module depends on perun AdapterRpc so it should be configured properly. However it tries to not rely on Perun system,
 * It should let user continue in authentication process despite Perun would be down or wouldnt work properly.
 *
 * Configuration:
 * 'attrMap' map from request (SAML2) attribute names to Perun attribute names.
 * 'extSourceNameAttr' request attribute name where entityID aka extSourceName is.
 * 		Hint: If you need to extract entityID use RetainIdPEntityID process filter.
 * 'uesLoginAttr' request attribute name where user login aka identifier is.
 *
 * example configuration:
 * 'extSourceNameAttr' => 'sourceIdPEntityID',
 * 'uesLoginAttr' => 'uid',
 * 'attrMap' => array(
 * 		'schacHomeOrganization' => 'urn:perun:ues:attribute-def:def:schacHomeOrganization',
 * 		'eduPersonScopedAffiliation' => 'urn:perun:ues:attribute-def:def:eduPersonScopedAffiliation',
 * ),
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_SavePerunUesAttrs extends SimpleSAML_Auth_ProcessingFilter
{
	private $attrMap;
	private $extSourceNameAttr;
	private $uesLoginAttr;

	/**
	 * @var sspmod_perun_AdapterRpc
	 */
	private $adapter;


	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		if (!isset($config['extSourceNameAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:SavePerunUesAttrs: missing mandatory configuration option 'extSourceNameAttr'.");
		}
		if (!isset($config['uesLoginAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:SavePerunUesAttrs: missing mandatory configuration option 'uesLoginAttr'.");
		}
		if (!isset($config['attrMap'])) {
			throw new SimpleSAML_Error_Exception("perun:SavePerunUesAttrs: missing mandatory configuration option 'attrMap'.");
		}

		$this->extSourceNameAttr = $config['extSourceNameAttr'];
		$this->uesLoginAttr = $config['uesLoginAttr'];
		$this->attrMap = $config['attrMap'];
		$this->adapter = sspmod_perun_Adapter::getInstance(sspmod_perun_Adapter::RPC);
	}


	public function process(&$request)
	{
		assert('is_array($request)');

		if (!isset($request['Attributes'][$this->extSourceNameAttr]) || !isset($request['Attributes'][$this->extSourceNameAttr][0])) {
			throw new SimpleSAML_Error_Exception(
				"perun:SavePerunUesAttrs: extSourceNameAttr wrongly configured. Value is not present in request attributes.");
		}
		$extSourceName = $request['Attributes'][$this->extSourceNameAttr][0];

		if (!isset($request['Attributes'][$this->uesLoginAttr]) || !isset($request['Attributes'][$this->uesLoginAttr][0])) {
			throw new SimpleSAML_Error_Exception(
				"perun:SavePerunUesAttrs: uesLoginAttr wrongly configured. Value is not present in request attributes.");
		}
		$uesLogin = $request['Attributes'][$this->uesLoginAttr][0];

		foreach ($this->attrMap as $samlAttr => $perunAttr) {

			if (isset($request['Attributes'][$samlAttr])) {
				$value = $request['Attributes'][$samlAttr];
			} else {
				$value = null;
			}

			$valueString = is_null($value) ? "null" : implode(",", $value);

			try {

				$ues = $this->adapter->getUserExtSource($extSourceName, $uesLogin);

				$this->adapter->setUserExtSourceAttribute($ues, $perunAttr, $value);

				$this->adapter->updateUserExtSourceLastAccess($ues);

				SimpleSAML_Logger::debug("perun:SavePerunUesAttrs: Attribute $samlAttr was saved to perun attribute $perunAttr. " .
					"Value: $valueString");

			} catch (sspmod_perun_Exception $e) {
				// This does not mean Perun is down. Rather something is wrongly configured.
				// sspmod_perun_Exception inherits from SimpleSAML_Error_Exception so there need to be another catch block.
				throw $e;
			} catch (SimpleSAML_Error_Exception $e) {
				// We do not want to rely on Perun.
				SimpleSAML_Logger::error("perun:SavePerunUesAttrs: Attribute $samlAttr was NOT saved to perun attribute $perunAttr. due to Error. " .
					"Value: $valueString, Error message: ". $e->getMessage() ."\n Trace: ". $e->getTraceAsString());
			}

		}

	}

}
