<?php

/**
 * CEITEC template for Discovery service
 *
 * Allow type hinting in IDE
 * @var sspmod_perun_DiscoTemplate $this
 */

$this->data['jquery'] = array('core' => TRUE, 'ui' => TRUE, 'css' => TRUE);

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' . SimpleSAML_Module::getModuleUrl('discopower/style.css')  . '" />';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' . SimpleSAML_Module::getModuleUrl('ceitec/res/css/disco.css')  . '" />';

$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/jquery.livesearch.js')  . '"></script>';
$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/quicksilver.js')  . '"></script>';

$this->data['head'] .= searchScript($faventry);


if (!empty($faventry)) $this->data['autofocus'] = 'favouritesubmit';


$this->includeAtTemplateBase('includes/header.php');



if (!empty($this->getPreferredIdp())) {

	echo '<p class="descriptionp">your previous selection</p>';
	echo '<div class="metalist list-group">';
	echo showEntry($this, $this->getPreferredIdp(), true);
	echo '</div>';


	echo getOr();
}



echo '<div class="row">';
foreach ($this->getIdps('social') AS $idpentry) {

	echo '<div class="col-md-4">';
	echo '<div class="metalist list-group">';
	echo showEntry($this, $idpentry);
	echo '</div>';
	echo '</div>';

}
echo '</div>';


echo getOr();



echo '<p class="descriptionp">';
echo 'your institutional account';
echo '</p>';


echo '<div class="inlinesearch">';
echo '	<form id="idpselectform" action="?" method="get">
			<input class="inlinesearchf form-control input-lg" placeholder="Type the name of your institution" 
			type="text" value="" name="query" id="query" autofocus oninput="document.getElementById(\'list\').style.display=\'block\';"/>
		</form>';
echo '</div>';


echo '<div class="metalist list-group" id="list">';

foreach ($this->getIdps('misc') AS $idpentry) {
	echo showEntry($this, $idpentry);
}
echo '</div>';


echo '<br>';
echo '<br>';

echo '<div class="no-idp-found alert alert-info">';
echo 'Can\'t find your institution? Contact us at <a href="mailto:idm@ics.muni.cz?subject=Request%20for%20adding%20new%20IdP">idm@ics.muni.cz</a>';
echo '</div>';

?>



<?php $this->includeAtTemplateBase('includes/footer.php');










function searchScript($faventry) {

	$script = '<script type="text/javascript">

	$(document).ready(function() { ';

	$script .= "\n" . '$("#query").liveUpdate("#list")' .
		(empty($faventry) ? '.focus()' : '') .
		';';

	$script .= '
	});
	
	</script>';

	return $script;
}


/**
 * @param sspmod_perun_DiscoTemplate $t
 * @param array $metadata
 * @param bool $favourite
 * @return string
 */
function showEntry($t, $metadata, $favourite = false) {

	if (in_array('social', $metadata['tags'])) {
		return showEntrySocial($t, $metadata, $favourite);
	}

	$extra = ($favourite ? ' favourite' : '');
	$html = '<a class="metaentry ' . $extra . ' list-group-item" href="' . $t->getContinueUrl($metadata['entityid']) . '">';

	$html .= '<strong>' . $t->getTranslatedEntityName($metadata) . '</strong>';

	$html .= '</a>';

	return $html;
}


/**
 * @param sspmod_perun_DiscoTemplate $t
 * @param array $metadata
 * @param bool $favourite
 * @return string
 */
function showEntrySocial($t, $metadata, $favourite = false) {

	$bck = 'white';
	if (!empty($metadata['color'])) {
		$bck = $metadata['color'];
	}

	$html = '<a class="btn btn-block social" href="' . $t->getContinueUrl($metadata['entityid']) . '" style="background: '. $bck .'">';

	$html .= '<img src="' . $metadata['icon'] . '">';

	$html .= '<strong>Sign in with ' . $t->getTranslatedEntityName($metadata) . '</strong>';

	$html .= '</a>';

	return $html;
}


function getOr() {
	$or  = '<div class="hrline">';
	$or .= '	<span>or</span>';
	$or .= '</div>';
	return $or;
}

