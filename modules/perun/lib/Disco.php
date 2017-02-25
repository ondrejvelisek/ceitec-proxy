<?php


/**
 * This class implements a IdP discovery service.
 *
 * This module extends the DiscoPower IdP disco handler, so it needs to be avaliable and enabled and configured.
 *
 * It adds functionality of whitelisting and greylisting IdPs.
 * for security reasons for blacklisting please manipulate directly with metadata. In case of manual idps
 * comment them out or in case of automated metadata fetching configure blacklist in config-metarefresh.php
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Disco extends sspmod_discopower_PowerIdPDisco
{
	private $originalsp;

	public function __construct(array $metadataSets, $instance)
	{
		parent::__construct($metadataSets, $instance);

		parse_str(parse_url($this->returnURL)['query'], $query);
		$id = explode(":", $query['AuthID'])[0];
		$state = SimpleSAML_Auth_State::loadState($id, 'saml:sp:sso');
		$this->originalsp = $state['SPMetadata'];
	}


	/**
	 * Handles a request to this discovery service. It is enry point of Discovery service.
	 *
	 * The IdP disco parameters should be set before calling this function.
	 */
	public function handleRequest()
	{
		// test if user has selected an idp or idp can be deremine automatically somehow.
		$this->start();

		// no choice possible. Show discovery service page
		$idpList = $this->getIdPList();
		$idpList = $this->filterList($idpList);
		$preferredIdP = $this->getRecommendedIdP();
		$preferredIdP = array_key_exists($preferredIdP, $idpList) ? $preferredIdP : null;

		if (sizeof($idpList) === 1) {
			$idp = array_keys($idpList)[0];
			$url = sspmod_perun_Disco::buildContinueUrl($this->spEntityId, $this->returnURL, $this->returnIdParam, $idp);
			SimpleSAML_Logger::info('perun.Disco: Only one Idp left. Redirecting automatically. IdP: ' . $idp);
			SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
		}

		$t = new sspmod_perun_DiscoTemplate($this->config);
		$t->data['originalsp'] = $this->originalsp;
		$t->data['idplist'] = $this->idplistStructured($idpList);
		$t->data['preferredidp'] = $preferredIdP;
		$t->data['entityID'] = $this->spEntityId;
		$t->data['return'] = $this->returnURL;
		$t->data['returnIDParam'] = $this->returnIdParam;
		$t->show();
	}


	/**
	 * Filter a list of entities according to any filters defined in the parent class, plus
	 *
	 * @param array $list A map of entities to filter.
	 * @return array The list in $list after filtering entities.
	 * @throws SimpleSAML_Error_Exception if all IdPs are filtered out and no one left.
	 */
	protected function filterList($list)
	{
		//SimpleSAML_Logger::debug('perun.Disco.filterList: Idps loaded from metadata: ' . var_export(array_keys($list), true));

		if (!isset($this->originalsp['disco.doNotFilterIdps']) || !$this->originalsp['disco.doNotFilterIdps']) {

			$list = parent::filterList($list);
			$list = $this->scoping($list);
			$list = $this->whitelisting($list);
			$list = $this->greylisting($list);
		}

		if (empty($list)) {
			throw new SimpleSAML_Error_Exception('All IdPs has been filtered out. And no one left.');
		}

		return $list;
	}

	/**
	 * Filter out IdP which are not in SAML2 Scoping attribute list (SAML2 feature)
	 * @param $list
	 * @return array of idps
	 */
	protected function scoping($list)
	{
		if (!empty($this->scopedIDPList)) {
			foreach ($list as $entityId => $idp) {
				if (!in_array($entityId, $this->scopedIDPList)) {
					unset($list[$entityId]);
				}
			}
		}
		//SimpleSAML_Logger::debug('perun.Disco.filterList: Idps after SAML2 Scoping: ' . var_export(array_keys($list), true));
		return $list;
	}


	protected function whitelisting($list)
	{
		$service = new sspmod_perun_IdpListsServiceCsv();
		foreach ($list as $entityId => $idp) {
			$unset = true;
			if ($service->isWhitelisted($entityId)) {
				$unset = false;
			}
			if (isset($idp['EntityAttributes']['http://macedir.org/entity-category-support'])) {
				$entityCategorySupport = $idp['EntityAttributes']['http://macedir.org/entity-category-support'];
				if (in_array("http://refeds.org/category/research-and-scholarship", $entityCategorySupport)) {
					$unset = false;
				} if (!in_array("http://www.geant.net/uri/dataprotection-code-of-conduct/v1", $entityCategorySupport)) {
					$unset = false;
				}
			}
			if (isset($idp['CoCo']) and $idp['CoCo'] === true) {
				$unset = false;
			}
			if (isset($idp['RaS']) and $idp['RaS'] === true) {
				$unset = false;
			}


			if ($unset === true) {
				unset($list[$entityId]);
			}
		}
		//SimpleSAML_Logger::debug('perun.Disco.filterList: Idps after Whitelisting: ' . var_export(array_keys($list), true));
		return $list;
	}


	protected function greylisting($list)
	{
		$service = new sspmod_perun_IdpListsServiceCsv();
		foreach ($list as $entityId => $idp) {
			if ($service->isGreylisted($entityId)) {
				unset($list[$entityId]);
			}
		}

		//SimpleSAML_Logger::debug('perun.Disco.filterList: Idps after Greylisting: ' . var_export(array_keys($list), true));
		return $list;
	}


	/**
	 * @param $entityID
	 * @param $return
	 * @param $returnIDParam
	 * @param $idpEntityId
	 * @return string url where user should be redirected when he choose idp
	 */
	public static function buildContinueUrl($entityID, $return, $returnIDParam, $idpEntityId) {
		$url = '?' .
			'entityID=' . urlencode($entityID) . '&' .
			'return=' . urlencode($return) . '&' .
			'returnIDParam=' . urlencode($returnIDParam) . '&' .
			'idpentityid=' . urlencode($idpEntityId);

		return $url;
	}


}
