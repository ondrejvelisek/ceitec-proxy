<?php

$faventry = getFaventry($this->data['preferredidp'], $this->data['idplist']);


if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);
$this->data['jquery'] = array('core' => TRUE, 'ui' => TRUE, 'css' => TRUE);

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' . SimpleSAML_Module::getModuleUrl('discopower/style.css')  . '" />';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' . SimpleSAML_Module::getModuleUrl('ceitec/res/css/disco.css')  . '" />';

$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/jquery.livesearch.js')  . '"></script>';
$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/' . $this->data['score'] . '.js')  . '"></script>';

$this->data['head'] .= searchScript($faventry);

parse_str(parse_url($this->data['return'])['query'], $query);
$id = explode(":", $query['AuthID'])[0];
$state = SimpleSAML_Auth_State::loadState($id, 'saml:sp:sso');
$spentry = $state['SPMetadata'];
$whitelist = file(SimpleSAML_Module::getModuleDir('ceitec/whitelist'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$greylist  = file(SimpleSAML_Module::getModuleDir('ceitec/greylist'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$blacklist = file(SimpleSAML_Module::getModuleDir('ceitec/blacklist'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);



if (!empty($faventry)) $this->data['autofocus'] = 'favouritesubmit';


$this->includeAtTemplateBase('includes/header.php');



if ($spentry['entityid'] !== 'https://perun.elixir-czech.cz/shibboleth/raw-attributes-all-idps') {


	if (!empty($faventry)) {

	echo '<p class="descriptionp">your previous selection</p>';
	echo '<div class="metalist list-group">';
	echo showEntry($this, $faventry, $spentry, true);
	echo '</div>';


	echo getOr();
    } 




    foreach( $this->data['idplist'] AS $slist) {

	if (empty($slist)) {
		continue;
	}

	// Have ORICD as the first one, so we reverse order
	$slist_reverse = $slist;
	arsort($slist_reverse);

	echo '<div class="row">';
	foreach ($slist_reverse AS $idpentry) {
		if (array_key_exists('social', $idpentry) && $idpentry['social'] === TRUE) {

			echo '<div class="col-md-4">';
			echo '<div class="metalist list-group">';
			echo showEntry($this, $idpentry, $spentry);
			echo '</div>';
			echo '</div>';
		}
	}
	echo '</div>';

    }


    echo getOr();



    echo '<p class="descriptionp">';
    echo 'your institutional account';
    echo '</p>';
}

echo '<div class="inlinesearch">';
echo '	<form id="idpselectform" action="?" method="get">
			<input class="inlinesearchf form-control input-lg" placeholder="Type the name of your institution" 
			type="text" value="" name="query" id="query" autofocus oninput="document.getElementById(\'list\').style.display=\'block\';"/>
		</form>';
echo '</div>';

foreach( $this->data['idplist'] AS $slist) {

	if (empty($slist)) {
		continue;
	}

	echo '<div class="metalist list-group" id="list">';

	foreach ($slist AS $idpentry) {
		if (isset($idpentry['social'])) {
			continue;
		}
		if (!filterIdp($idpentry, $spentry, $whitelist, $greylist, $blacklist)) {
			continue;
		}
		echo (showEntry($this, $idpentry, $spentry));
	}
	echo '</div>';
}

echo '<br>';
echo '<br>';

echo '<div class="no-idp-found alert alert-info">';
	echo 'Can\'t find your institution? Contact us at <a href="mailto:idm@ics.muni.cz?subject=Request%20for%20adding%20new%20IdP">idm@ics.muni.cz</a>';
echo '</div>';

?>



<?php $this->includeAtTemplateBase('includes/footer.php');



















function getFaventry($preferredidp, $idplist) {
	if (!empty($preferredidp)) {
		foreach( $idplist AS $tab => $slist) {
			if (array_key_exists($preferredidp, $slist)) {
				return $slist[$preferredidp];
			}
		}
	}
	return null;
}





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



/*
 Return true if idp should stay in the discovery list. false otherwise.
*/
function filterIdp($idpentry, $spentry, $whitelist, $greylist, $blacklist) {
	$whitelist = isset($whitelist) ? $whitelist : array();
	$greylist  = isset($greylist)  ? $greylist  : array();
	$blacklist = isset($blacklist) ? $blacklist : array();	

	if (in_array($idpentry['entityid'], $blacklist)) {
                return false;
        }
	if (isset($spentry['disco.showAllIdps']) && $spentry['disco.showAllIdps']) {
		return true;
	}
	if (in_array($idpentry['entityid'], $greylist)) {
                return false;
        }
	if (isset($idpentry['EntityAttributes']['http://macedir.org/entity-category-support'])) {
		$entityCategorySupport = $idpentry['EntityAttributes']['http://macedir.org/entity-category-support'];
		if (in_array("http://refeds.org/category/research-and-scholarship", $entityCategorySupport)) {
			return true;
		}
		if (in_array("http://www.geant.net/uri/dataprotection-code-of-conduct/v1", $entityCategorySupport)) {
			return true;
		}
	}
	if (isset($idpentry['CoCo']) && $idpentry['CoCo']) {
		return true;	
	}
	if (in_array($idpentry['entityid'], $whitelist)) {
		return true;
	}	
	return false;
}



function showEntry($t, $metadata, $spentry, $favourite = FALSE) {

	if (!empty($metadata['social'])) {
		return showEntrySocial($t, $metadata, $spentry, $favourite);
	}

	$extra = ($favourite ? ' favourite' : '');
	$html = '<a class="metaentry' . $extra . ' list-group-item" href="' . continueUrl($t, $metadata['entityid'], $spentry) . '">';

	$html .= '<strong>' . htmlspecialchars(getTranslatedName($t, $metadata)) . '</strong>';

	$html .= showIcon($metadata);

	$html .= '</a>';

	return $html;
}

function showEntrySocial($t, $metadata, $spentry, $favourite) {
	
	$bck = 'white';
	if (!empty($metadata['color'])) {
		$bck = $metadata['color'];
	}

	$html = '<a class="btn btn-block social" href="' . continueUrl($t, $metadata['entityid'], $spentry)  . '" style="background: '. $bck .'">';

	$html .= '<img src="' . $metadata['icon'] . '">';

	$html .= '<strong>Sign in with ' . htmlspecialchars(getTranslatedName($t, $metadata)) . '</strong>';

	$html .= '</a>';

	return $html;
}

function continueUrl($t, $idpEntityId, $spentry) {
	$url = '?' .
                'entityID=' . urlencode($t->data['entityID']) . '&' .
                'return=' . urlencode($t->data['return']) . '&' .
                'returnIDParam=' . urlencode($t->data['returnIDParam']) . '&' .
                'idpentityid=' . urlencode($idpEntityId);

        if (isset($spentry['disco.showAllIdps']) && $spentry['disco.showAllIdps']) {
                $url .= '&amp;doNotSaveIdP=true';
        }

	return $url;
}

function showIcon($metadata) {
	$html = '';
	// Logos are turned off, because they are loaded via URL from IdP. Some IdPs have bad configuration, so it breaks the WAYF.
	
	/*if (isset($metadata['UIInfo']['Logo'][0]['url'])) {
		$html .= '<img src="' . htmlspecialchars(\SimpleSAML\Utils\HTTP::resolveURL($metadata['UIInfo']['Logo'][0]['url'])) . '" class="idp-logo">';
	} else if (isset($metadata['icon'])) {
		$html .= '<img src="' . htmlspecialchars(\SimpleSAML\Utils\HTTP::resolveURL($metadata['icon'])) . '" class="idp-logo">';
	}*/
	
	return $html;
}




function getTranslatedName($t, $metadata) {
	if (isset($metadata['UIInfo']['DisplayName'])) {
		$displayName = $metadata['UIInfo']['DisplayName'];
		assert('is_array($displayName)'); // Should always be an array of language code -> translation
		if (!empty($displayName)) {
			return $t->getTranslation($displayName);
		}
	}

	if (array_key_exists('name', $metadata)) {
		if (is_array($metadata['name'])) {
			return $t->getTranslation($metadata['name']);
		} else {
			return $metadata['name'];
		}
	}
	return $metadata['entityid'];
}



function getOr() {
	$or  = '<div class="hrline">';
	$or .= '	<span>or</span>';
	$or .= '</div>';
	return $or;
}

