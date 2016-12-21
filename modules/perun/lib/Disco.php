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


	public function __construct(array $metadataSets, $instance)
	{
		parent::__construct($metadataSets, $instance);
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
		$preferredIdP = $this->getRecommendedIdP();
		$idpList = $this->idplistStructured($this->filterList($idpList));

		$t = new sspmod_perun_DiscoTemplate($this->config);
		$t->data['idplist'] = $idpList;
		$t->data['preferredidp'] = $preferredIdP;
		$t->data['return'] = $this->returnURL;
		$t->data['returnIDParam'] = $this->returnIdParam;
		$t->data['entityID'] = $this->spEntityId;
		$t->show();
	}


	/**
	 * Filter a list of entities according to any filters defined in the parent class, plus
	 *
	 * @param array $list A list of entities to filter.
	 *
	 * @return array The list in $list after filtering entities.
	 */
	protected function filterList($list)
	{
		$list = parent::filterList($list);

		return $list;
	}



}
