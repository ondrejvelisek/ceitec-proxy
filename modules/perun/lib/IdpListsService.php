<?php

/**
 * This interface provides abstraction of manipulation with lists of IdPs
 * saved and managed by Proxy IdP. e.g. Whitelist or greylist.
 * It should abstract from a form how the data is stored.
 *
 * IdP here is represented by an associative array with keys:
 * 	entityid, timestamp and optionally reason.
 * when the IdP was added or lately modified.
 *
 * Note that implementation should be thread/concurrency safe.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
interface sspmod_perun_IdpListsService
{

	/**
	 * @return array of all whitelisted IdPs.
	 * Note that IdP can be generally present more than one times with different timestamp.
	 * If you want just latest idps use other getLatestWhitelist method.
	 */
	function getWhitelist();

	/**
	 * @return array of all latest (by timestamp) whitelisted IdPs.
	 * note that each IdP can be presented only once with the latest timestamp.
	 */
	function getLatestWhitelist();

	/**
	 * @param $entityID
	 * @return array of whitelist entries with the given entityID.
	 */
	function getWhitelistForIdp($entityID);

	/**
	 * @param string $entityID
	 * @return bool true if whitelist contains given entityID, false otherwise.
	 */
	function isWhitelisted($entityID);

	/**
	 * Adds IdP with given entityID to whitelist and generate current timestamp.
	 * @param string $entityID
	 * @param null|string $reason
	 */
	function addIdpToWhitelist($entityID, $reason = null);

	/**
	 * Remove IdP with given entityID from whitelist with all its occurrences.
	 * Therefore should be used wisely
	 * @param string $entityID
	 */
	function removeIdpFromWhitelist($entityID);



	/**
	 * @return array of all greylisted IdPs.
	 * Note that IdP can be generally present more than one times with different timestamp.
	 * If you want just latest idps use other getLatestGreylist method.
	 */
	function getGreylist();

	/**
	 * @return array of all latest (by timestamp) greylisted IdPs.
	 * note that each IdP can be presented only once with the latest timestamp.
	 */
	function getLatestGreylist();

	/**
	 * @param $entityID
	 * @return array of whitelist entries with the given entityID.
	 */
	function getGreylistForIdp($entityID);

	/**
	 * @param string $entityID
	 * @return bool true if greylist contains given entityID, false otherwise.
	 */
	function isGreylisted($entityID);

	/**
	 * Adds IdP with given entityID to greylist and generate current timestamp.
	 * @param string $entityID
	 * @param null|string $reason
	 */
	function addIdpToGreylist($entityID, $reason = null);

	/**
	 * Remove IdP with given entityID from greylist with all its occurrences.
	 * @param string $entityID
	 */
	function removeIdpFromGreylist($entityID);


	/**
	 * Basically do the same as addIdpToWhitelist and removeIdpFromGreylist methods.
	 * Note implementation should take care of transaction.
	 * @param string $entityID
	 * @param null|string $reason
	 */
	function whitelistIdp($entityID, $reason = null);
}
