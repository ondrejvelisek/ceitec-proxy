<?php

/**
* Process filter which alllows just users from defined group to access test SPs.
*
* Author: Michal Prochazka <michalp@ics.muni.cz>
*/

class sspmod_perun_Auth_Process_TestSPs extends SimpleSAML_Auth_ProcessingFilter
{
    private $attrName;
    private $testGroupName =  'test.sp.groupname';
    private $testSPFlag = 'test.sp';

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        if (!isset($config['attrName'])) {
            throw new SimpleSAML_Error_Exception("perun:TestSPs: missing mandatory configuration option 'attrName'.");
        }

        $this->attrName = (string) $config['attrName'];
    }

    public function process(&$request)
    {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');

        if (array_key_exists($this->testSPFlag, $request["Destination"]) && $request["Destination"]["test.sp"] === true) {

            $groupName = '';
            if (array_key_exists($this->testGroupName, $request["Destination"])) {
                $groupName = $request["Destination"][$this->testGroupName];
            } else {
                log('No test group name defined in SP metadata, use test.sp.groupname attribute.');
            }

            $attributes = $request['Attributes'];
            $groups = array();

            if (array_key_exists($this->attrName, $attributes)) {
                $groups = $attributes[$this->attrName];

                assert('is_array($groups)');

                if (!in_array($groupName, $groups)) {
                    // User is not in a group
                    $this->unauthorized($request);
                }
            } else {
                // No groups defined
                $this->unauthorized($request);
            }
        }
    }
    protected function log($message)
    {
        SimpleSAML_Logger::info('perun.TestSPs: '.$message);
    }

    /**
     * When the process logic determines that the user is not
     * authorized for this service, then forward the user to
     * an 403 unauthorized page.
     *
     * Separated this code into its own method so that child
     * classes can override it and change the action. Forward
     * thinking in case a "chained" ACL is needed, more complex
     * permission logic.
     *
     * @param array $request
     */
    protected function unauthorized(&$request) {
            // Save state and redirect to 403 page
            $id = SimpleSAML_Auth_State::saveState($request,
                    'authorize:Authorize');
            $url = SimpleSAML_Module::getModuleURL(
                    'authorize/authorize_403.php');
            \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
    }
}
