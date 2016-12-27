<?php

/**
 * This is simple example of template where user can choose where they want to register to access the requested service
 *
 * Allow type hinting in IDE
 * @var SimpleSAML_XHTML_Template $this
 */

$this->data['header'] = 'Select group which fits you most';

$this->includeAtTemplateBase('includes/header.php');

echo 'It will give you access to the requested service.';

echo '<div class="list-group">';
foreach ($this->data['groups'] as $group) {
	$url = getRegisterUrl($this->data['redirect'], $this->data['callbackParamName'], $this->data['callback'], $this->data['vo']['shortName'], $group['name']);
	echo "<a href='$url' class='list-group-item'><b>{$group['name']}</b> - {$group['description']}</a>";
}
echo '</div>';



$this->includeAtTemplateBase('includes/footer.php');



/**
 * @param $redirect
 * @param $callbackParamName
 * @param $callback
 * @param $voShortName
 * @param $groupName
 * @return string url where user should continue to register to group
 */
function getRegisterUrl($redirect, $callbackParamName, $callback, $voShortName, $groupName) {
	return \SimpleSAML\Utils\HTTP::addURLParameters($redirect, array(
		'vo' => $voShortName,
		'group' => $groupName,
		$callbackParamName => $callback,
	));
}