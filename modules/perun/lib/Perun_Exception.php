<?php

/**
 *
 */
class Perun_Exception extends SimpleSAML_Error_Exception
{
	private $id;
	private $name;
	private $message;

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

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}





}