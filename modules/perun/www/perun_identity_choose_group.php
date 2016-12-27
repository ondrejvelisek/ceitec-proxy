<?php
/**
 * This page let user select one group and redirect him to a url where he can register to group.
 *
 * It prepares model data for Template.
 *
 * See sspmod_perun_Auth_Process_PerunIdentity for mor information.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

$adapter = sspmod_perun_Adapter::getInstance($_REQUEST['interface']);


$vo = $adapter->getVoByShortName($_REQUEST['voShortName']);


$groups = array();
foreach ($_REQUEST['groupNames'] as $groupName) {
	array_push($groups, $adapter->getGroupByName($vo['id'], $groupName));
}


$config = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($config, 'perun:choose-group-tpl.php');
$t->data['redirect'] = $_REQUEST['redirect'];
$t->data['callback'] = $_REQUEST['callback'];
$t->data['callbackParamName'] = $_REQUEST['callbackParamName'];
$t->data['vo'] = $vo;
$t->data['groups'] = $groups;
$t->show();