<?php

/**
 * Wrapper of Perun exception returned from RPC.
 *
 * It extends SimpleSAML_Error_Exception because user we want that user can report it.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Exception extends SimpleSAML_Error_Exception
{
	private $id;
	private $name;
	// note that field $message is inherited

	/**
	 * Perun_Exception constructor.
	 * @param string $id
	 * @param string $name
	 * @param string $message
	 */
	public function __construct($id, $name, $message)
	{
		parent::__construct("Perun error: $id - $name - $message");

		$this->id = $id;
		$this->name = $name;
		$this->message = $message;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


}