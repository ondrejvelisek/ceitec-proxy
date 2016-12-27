<?php

/**
 * Interface sspmod_perun_Adapter
 * specify interface to get information from Perun.
 */
abstract class sspmod_perun_Adapter
{
	const RPC = 'rpc';

	/**
	 * @param string $interface code of interface. Check constants of this class.
	 * @return sspmod_perun_Adapter instance of this class. note it is NOT singleton.
	 * @throws SimpleSAML_Error_Exception thrown if interface does not match any supported interface
	 */
	public static function getInstance($interface) {
		if ($interface === self::RPC) {
			return new sspmod_perun_AdapterRpc();
		} else {
			throw new SimpleSAML_Error_Exception('Unknown perun interface. Hint: try ' . self::RPC);
		}
	}

	/**
	 * @param string $idpEntityId entity id of hosted idp used as extSourceName
	 * @param string $uid user identifier received from remote idp used as userExtSourceLogin
	 * @return array perun user contains keys id, firstName, middleName, lastName
	 */
	public abstract function getPerunUser($idpEntityId, $uid);

	/**
	 * @param int $vo vo id
	 * @param string $name group name
	 * @return array group contains keys id, name and description
	 */
	public abstract function getGroupByName($vo, $name);

	/**
	 * @param string $voShortName
	 * @return array vo contains keys id, name, shortName
	 */
	public abstract function getVoByShortName($voShortName);

	/**
	 * @param int $perunUid perun user id
	 * @param string $voShortName short name of vo we are working with. Note current proxy can work only with one VO.
	 * @return array groups from vo which member is including members group. Contains keys id, name and description.
	 */
	public abstract function getMemberGroups($perunUid, $voShortName);

	/**
	 * @param string $spEntityId entity id of the sp
	 * @param string $voShortName
	 * @return array groups from vo which are assigned to all facilities with spEntityId. Contains keys id, name and description.
	 * registering to those groups should should allow access to the service
	 */
	public abstract function getSpGroups($spEntityId, $voShortName);

	/**
	 * @param int $perunUid
	 * @param array $attrNames.
	 * @return array of attributes. Contains keys id, namespace, friendlyName and value
	 */
	public abstract function getUserAttributes($perunUid, $attrNames);

}