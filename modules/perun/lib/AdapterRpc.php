<?php

/**
 * Class sspmod_perun_AdapterRpc
 *
 * Perun adapter which uses Perun RPC interface
 */
class sspmod_perun_AdapterRpc extends sspmod_perun_Adapter
{


	public function getPerunUser($idpEntityId, $uid)
	{
		try {
			$user = sspmod_perun_RpcConnector::get('usersManager', 'getUserByExtSourceNameAndExtLogin', array(
				'extSourceName' => $idpEntityId,
				'extLogin' => $uid,
			));

			$name = '';
			if (!empty($user['titleBefore'])) $name .= $user['titleBefore'].' ';
			if (!empty($user['titleBefore'])) $name .= $user['firstName'].' ';
			if (!empty($user['titleBefore'])) $name .= $user['middleName'].' ';
			if (!empty($user['titleBefore'])) $name .= $user['lastName'];
			if (!empty($user['titleBefore'])) $name .= ' '.$user['titleAfter'];

			return new sspmod_perun_model_User($user['id'], $name);
		} catch (sspmod_perun_Exception $e) {
			if ($e->getName() === 'UserExtSourceNotExistsException') {
				return null;
			} else if ($e->getName() === 'ExtSourceNotExistsException') {
				// Because use of original/source entityID as extSourceName
				return null;
			} else {
				throw $e;
			}
		}
	}


	public function getMemberGroups($user, $vo)
	{
		$member = sspmod_perun_RpcConnector::get('membersManager', 'getMemberByUser', array(
			'vo' => $vo->getId(),
			'user' => $user->getId(),
		));

		$memberGroups = sspmod_perun_RpcConnector::get('groupsManager', 'getAllMemberGroups', array(
			'member' => $member['id'],
		));

		$convertedGroups = array();
		foreach ($memberGroups as $group) {
			array_push($convertedGroups, new sspmod_perun_model_Group($group['id'], $group['name'], $group['description']));
		}

		return $convertedGroups;
	}


	public function getSpGroups($spEntityId, $vo)
	{
		$resources = sspmod_perun_RpcConnector::get('resourcesManager', 'getResources', array(
			'vo' => $vo->getId(),
		));

		$spFacilityIds = array();
		$spResources = array();
		foreach ($resources as $resource) {
			if (!array_key_exists($resource['facilityId'], $spFacilityIds)) {
				$attribute = sspmod_perun_RpcConnector::get('attributesManager', 'getAttribute', array(
					'facility' => $resource['facilityId'],
					'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
				));
				if ($attribute['value'] === $spEntityId) {
					$spFacilityIds[$resource['facilityId']] = true;
				} else {
					$spFacilityIds[$resource['facilityId']] = false;
				}
			}
			if ($spFacilityIds[$resource['facilityId']]) {
				array_push($spResources, $resource);
			}
		}

		$spGroups = array();
		foreach ($spResources as $spResource) {
			$groups = sspmod_perun_RpcConnector::get('resourcesManager', 'getAssignedGroups', array(
				'resource' => $spResource['id'],
			));
			$convertedGroups = array();
			foreach ($groups as $group) {
				array_push($convertedGroups, new sspmod_perun_model_Group($group['id'], $group['name'], $group['description']));
			}
			$spGroups = array_merge($spGroups, $convertedGroups);
		}

		$spGroups = $this->removeDuplicateEntities($spGroups);

		return $spGroups;
	}


	public function getGroupByName($vo, $name)
	{
		$group = sspmod_perun_RpcConnector::get('groupsManager', 'getGroupByName', array(
			'vo' => $vo->getId(),
			'name' => $name,
		));

		return new sspmod_perun_model_Group($group['id'], $group['name'], $group['description']);
	}


	public function getVoByShortName($voShortName)
	{
		$vo = sspmod_perun_RpcConnector::get('vosManager', 'getVoByShortName', array(
			'shortName' => $voShortName,
		));

		return new sspmod_perun_model_Vo($vo['id'], $vo['name'], $vo['shortName']);
	}


	public function getUserAttributes($user, $attrNames)
	{
		$perunAttrs = sspmod_perun_RpcConnector::get('attributesManager', 'getAttributes', array(
			'user' => $user->getId(),
			'attrNames' => $attrNames,
		));

		$attributes = array();
		foreach ($perunAttrs as $perunAttr) {

			$perunAttrName = $perunAttr['namespace'] . ":" . $perunAttr['friendlyName'];

			$attributes[$perunAttrName] = $perunAttr['value'];
		}

		return $attributes;
	}


	public function getUserExtSource($extSourceName, $userExtSourceLogin)
	{
		$extSource = sspmod_perun_RpcConnector::get("extSourcesManager", "getExtSourceByName", array(
			"name" => $extSourceName
		));

		// ::post because it cannot parsed correctly JSON object to URL params correctly
		$ues = sspmod_perun_RpcConnector::post("usersManager", "getUserExtSourceByExtLogin", array(
			"extSource" => $extSource,
			"extSourceLogin" => $userExtSourceLogin
		));

		return new sspmod_perun_model_UserExtSource($ues['id'], $ues['login'], $ues['userId'], $ues['loa']);
	}


	/**
	 * Updates last access property of given UES. Last access property defines when user authenticates using this UES.
	 * @param sspmod_perun_model_UserExtSource $userExtSource
	 * @return void
	 * @throws SimpleSAML_Error_Exception if Perun is inaccessible (Not only, such exception can be thrown with different causes)
	 */
	public function updateUserExtSourceLastAccess($userExtSource)
	{
		sspmod_perun_RpcConnector::post("usersManager", "updateUserExtSourceLastAccess", array(
			"userExtSource" => $userExtSource->getId()
		));
	}


	/**
	 * Set user external source attribute to Perun.
	 * @param sspmod_perun_model_UserExtSource $userExtSource
	 * @param string $attrName
	 * @param array $attrValue new value of attribute
	 * @return void
	 * @throws SimpleSAML_Error_Exception if Perun is inaccessible (Not only, such exception can be thrown with different causes)
	 */
	public function setUserExtSourceAttribute($userExtSource, $attrName, $attrValue)
	{
		$attribute = sspmod_perun_RpcConnector::get("attributesManager", "getAttribute", array(
			"userExtSource" => $userExtSource->getId(),
			"attributeName" => $attrName
		));

		$attribute['value'] = $attrValue;

		sspmod_perun_RpcConnector::post("attributesManager", "setAttribute", array(
			"userExtSource" => $userExtSource->getId(),
			"attribute" => $attribute
		));
	}

}