<?php

/**
 * Class sspmod_perun_Auth_Process_PerunGroups
 *
 * This filter simply extracts group names from cached groups from PerunIdentity filter and save them into attribute.
 * It means it strongly relays on it.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_PerunGroups extends SimpleSAML_Auth_ProcessingFilter
{

	private $attrName;

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		if (!isset($config['attrName'])) {
			throw new SimpleSAML_Error_Exception("perun:PerunGroups: missing mandatory configuration option 'attrName'.");
		}
		$this->attrName = (string) $config['attrName'];
	}


	public function process(&$request)
	{
		if (isset($request['perun']['groups'])) {
			$groups = $request['perun']['groups'];
		} else {
			throw new SimpleSAML_Error_Exception("perun:PerunGroups: " .
				"missing mandatory field 'perun.groups' in request." .
				"Hint: Did you configured PerunIdentity filter before this filter?"
			);
		}

		$request['Attributes'][$this->attrName] = array();
		foreach ($groups as $group) {
			array_push($request['Attributes'][$this->attrName], $group['name']);
		}
	}

}
