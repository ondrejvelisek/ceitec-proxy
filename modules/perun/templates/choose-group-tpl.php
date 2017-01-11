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
	$url = getRegisterUrl($this->data['registerUrl'], $this->data['callbackParamName'], $this->data['callbackUrl'], $this->data['vo']['shortName'], $group['name']);
	echo "<a href='$url' class='list-group-item'><b>{$group['name']}</b> - {$group['description']}</a>";
}
echo '</div>';



$this->includeAtTemplateBase('includes/footer.php');



/**
 * @param $registerUrl
 * @param $callbackParamName
 * @param $callbackUrl
 * @param $voShortName
 * @param $groupName
 * @return string url where user should continue to register to group
 */
function getRegisterUrl($registerUrl, $callbackParamName, $callbackUrl, $voShortName, $groupName) {
	return \SimpleSAML\Utils\HTTP::addURLParameters($registerUrl, array(
		'vo' => $voShortName,
		'group' => $groupName,
		$callbackParamName => $callbackUrl,
	));
}