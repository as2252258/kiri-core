<?php


namespace Rpc;


use Annotation\Inject;
use Exception;
use Snowflake\Snowflake;

/**
 * Class Consumer
 * @package Rpc
 */
abstract class Consumer implements IProducer
{


	protected ?Client $client = null;


	#[Inject('rpc')]
	public ?Producer $rpc = null;


	/**
	 * @return Client|null
	 */
	public function initClient(): ?Client
	{
		return $this->client;
	}


	/**
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get(string $name): mixed
	{
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->{$method}();
		}
		return Snowflake::app()->get($name);
	}


}
