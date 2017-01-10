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
			return $user;
		} catch (sspmod_perun_Exception $e) {
			if ($e->getName() === 'UserExtSourceNotExistsException' || $e->getName() === 'ExtSourceNotExistsException') {
				return null;
			} else {
				throw $e;
			}
		}
	}


	public function getMemberGroups($perunUid, $voShortName)
	{
		$vo = sspmod_perun_RpcConnector::get('vosManager', 'getVoByShortName', array(
			'shortName' => $voShortName,
		));

		$member = sspmod_perun_RpcConnector::get('membersManager', 'getMemberByUser', array(
			'vo' => $vo['id'],
			'user' => $perunUid,
		));

		$memberGroups = sspmod_perun_RpcConnector::get('groupsManager', 'getAllMemberGroups', array(
			'member' => $member['id'],
		));

		return $memberGroups;
	}


	public function getSpGroups($spEntityId, $voShortName)
	{
		$vo = sspmod_perun_RpcConnector::get('vosManager', 'getVoByShortName', array(
			'shortName' => $voShortName,
		));

		$resources = sspmod_perun_RpcConnector::get('resourcesManager', 'getResources', array(
			'vo' => $vo['id'],
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
			$spGroups = array_merge($spGroups, $groups);
		}

		$spGroups = $this->removeDuplicatesById($spGroups);

		return $spGroups;
	}


	private function removeDuplicatesById($entities) {

		$removed = array();
		$ids = array();
		foreach ($entities as $entity) {
			if (!in_array($entity['id'], $ids)) {
				array_push($ids, $entity['id']);
				array_push($removed, $entity);
			}
		}
		return $removed;

	}


	public function getGroupByName($vo, $name)
	{
		return sspmod_perun_RpcConnector::get('groupsManager', 'getGroupByName', array(
			'vo' => $vo,
			'name' => $name,
		));
	}


	public function getVoByShortName($voShortName)
	{
		return sspmod_perun_RpcConnector::get('vosManager', 'getVoByShortName', array(
			'shortName' => $voShortName,
		));
	}


	public function getUserAttributes($perunUid, $attrNames)
	{
		return sspmod_perun_RpcConnector::get('attributesManager', 'getAttributes', array(
			'user' => $perunUid,
			'attrNames' => $attrNames,
		));
	}
}
