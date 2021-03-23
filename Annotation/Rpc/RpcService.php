<?php


namespace Annotation\Rpc;


use Annotation\Attribute;
use ReflectionException;
use Rpc\IProducer;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * Class RpcClient
 * @package Annotation\Rpc
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class RpcService extends Attribute
{

	private array $config;


	/**
	 * RpcClient constructor.
	 * @param string $cmd
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 */
	public function __construct(
		public string $cmd,
		public string $host,
		public int $port,
		public int $mode = SWOOLE_SOCK_TCP6
	)
	{
		$this->config = ['host' => $this->host, 'port' => $this->port, 'mode' => $this->mode];
	}


	/**
	 * @param array $handler
	 * @return mixed
	 * @throws ReflectionException
	 * @throws ComponentException
	 * @throws NotFindClassException
	 */
	public function execute(array $handler): mixed
	{
		$rpc = Snowflake::app()->getRpc();
		$rpc->addProducer($this->cmd, $handler, $this->config);
		return parent::execute($handler);
	}


}
