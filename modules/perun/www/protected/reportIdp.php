<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo 'ERROR request has to be POST';
	die;
}
if (!isset($_POST['idpEntityId'])) {
	http_response_code(400);
	echo 'ERROR parametr "idpEntityId" is missing';
	die;
}
if (!isset($_POST['isOk'])) {
	http_response_code(400);
	echo 'ERROR parametr "isOk" is missing';
	die;
}
if (!isset($_POST['redirectUri'])) {
	http_response_code(400);
	echo 'ERROR parametr "redirectUri" is missing';
	die;
}


$config = SimpleSAML_Configuration::getInstance();

$message = <<<EOD

User message: {$_POST['body']}

IdP name displayed to user: {$_POST['idpDisplayName']}
IdP entityId: {$_POST['idpEntityId']}

Released all attributes: {$_POST['isOk']}
 - user's identifier: {$_POST['hasUid']}
 - user's affiliation: {$_POST['hasAffiliation']}
 - user's organization: {$_POST['hasOrganization']}

Time of the check: {$_POST['time']}

Result were saved on machine: {$_POST['resultInFile']}
IdP were whitelisted automatically: {$_POST['resultOnProxy']}

EOD;

$toAddress = $config->getString('technicalcontact_email', 'N/A');
if ($toAddress !== 'N/A') {
	$email = new SimpleSAML_XHTML_EMail($toAddress, 'Report: '.$_POST['title'], $_POST['from']);
	$email->setBody($message);
	$email->send();
}

echo '<h1>Unssuported redirection</h1>';

echo "Go back to <a href='{$_POST['redirectUri']}'>{$_POST['redirectUri']}</a>";

// redirect the user back
\SimpleSAML\Utils\HTTP::redirectTrustedURL($_POST['redirectUri'], array('mailSended' => true));





?>
