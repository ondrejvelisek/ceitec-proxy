<?php

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_model_UserExtSource implements sspmod_perun_model_HasId
{
	private $id;
	private $login;
	private $userId;
	private $loa;

	/**
	 * sspmod_perun_model_UserExtSource constructor.
	 * @param int $id
	 * @param string $login
	 * @param int $userId
	 * @param int $loa
	 */
	public function __construct($id, $login, $userId, $loa)
	{
		$this->id = $id;
		$this->login = $login;
		$this->userId = $userId;
		$this->loa = $loa;
	}


	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @return int
	 */
	public function getLoa()
	{
		return $this->loa;
	}




}