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
		SimpleSAML_Logger::debug('perun.Disco.filterList: Idps loaded from metadata: ' . var_export(array_keys($list), true));

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


	protected function scoping($list)
	{
		if (!empty($this->scopedIDPList)) {
			foreach ($list as $entityId => $idp) {
				if (!in_array($entityId, $this->scopedIDPList)) {
					unset($list[$entityId]);
				}
			}
		}
		SimpleSAML_Logger::debug('perun.Disco.filterList: Idps after SAML2 Scoping: ' . var_export(array_keys($list), true));
		return $list;
	}


	protected function whitelisting($list)
	{
		$whitetable = $this->readTableFromFile('whitelist', array(0 => 'date', 1 => 'entityId'));
		if ($whitetable !== null) {
			$whitelist = isset($whitelist) ? $whitelist : array();
			foreach ($whitetable as $row) {
				array_push($whitelist, $row['entityId']);
			}
		}

		if (isset($whitelist)) {
			foreach ($list as $entityId => $idp) {
				if (!in_array($entityId, $whitelist)) {
					unset($list[$entityId]);
				}
			}
		}
		SimpleSAML_Logger::debug('perun.Disco.filterList: Idps after Whitelisting: ' . var_export(array_keys($list), true));
		return $list;
	}


	protected function greylisting($list)
	{
		$greytable = $this->readTableFromFile('greylist', array(0 => 'date', 1 => 'entityId'));
		if ($greytable !== null) {
			$greylist = isset($greylist) ? $greylist : array();
			foreach ($greytable as $row) {
				array_push($greylist, $row['entityId']);
			}
		}


		if (isset($greylist)) {
			foreach ($greylist as $entityId) {
				unset($list[$entityId]);
			}
		}
		SimpleSAML_Logger::debug('perun.Disco.filterList: Idps after Greylisting: ' . var_export(array_keys($list), true));
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


	/**
	 * get list of associative arrays based on given file. It ignores commented lines with # or //.
	 *
	 * @param string $filepath relative to config directory or absolute path.
	 * @param array $colMap associative array
	 * @return array of arrays.
	 */
	private function readTableFromFile($filepath, $colMap = null) {

		if (substr($filepath, 0, 1) !== '/') {
			$filepath = SimpleSAML\Utils\Config::getConfigDir() . '/' . $filepath;
		}

		$table = array();

		if (!file_exists($filepath)) {
			return null;
		}

		$lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			throw new SimpleSAML_Error_Exception('Error while opening file: ' . $filepath);
		}


		foreach ($lines as $line) {
			$line = trim($line);

			// ignore commented lines
			if (substr($line, 0, 1) === '#' || substr($line, 0, 2) === '//') {
				continue;
			}

			$rawRow = preg_split('/\s+/', $line);


			if (is_null($colMap)) {
				$row = $rawRow;
			} else {
				foreach ($colMap as $colId => $colName) {
					$row[$colName] = $rawRow[$colId];
				}
			}

			array_push($table, $row);
		}

		return $table;

	}



}
