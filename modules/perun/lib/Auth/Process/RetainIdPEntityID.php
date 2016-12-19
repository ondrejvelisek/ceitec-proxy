<?php

/**
 * Class sspmod_perun_Auth_Process_RetainIdPEntityID
 *
 * Filter extract entityID of source remote IdP.
 * It supposed to be used in proxy SP context. Means it should be defined in authsources or idp-remote files.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_RetainIdPEntityID extends SimpleSAML_Auth_ProcessingFilter
{
	private $attrName = 'sourceIdPEntityID';

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		# Target attribute can be set in config, if not, the the default is used
		if (isset($config['attrName'])) {
			$this->attrName = $config['attrName'];
		}

	}


	public function process(&$request)
	{
		assert('is_array($request)');

		if (isset($request['Source']['entityid'])) {
			$entityId = $request['Source']['entityid'];
		} else {
			throw new SimpleSAML_Error_Exception("perun:RetainIdPEntityID: Cannot find entityID of remote IDP. " .
				"hint: Do you have this filter in SP context?");
		}

		$request['Attributes'][$this->attrName] = array($entityId);
	}


}

