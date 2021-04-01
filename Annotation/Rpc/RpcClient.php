<?php


namespace Annotation\Rpc;


use Annotation\Attribute;
use Exception;
use Snowflake\Snowflake;


/**
 * Class RpcClient
 * @package Annotation\Rpc
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class RpcClient extends Attribute
{

	private array $config;


	/**
	 * RpcClient constructor.
	 * @param string $cmd
	 * @param int $port
	 * @param int $timeout
	 * @param int $mode
	 */
	public function __construct(
		public string $cmd,
		public int $port,
		public int $timeout,
		public int $mode
	)
	{
		$this->config = ['port' => $port, 'mode' => $mode, 'timeout' => $timeout];
	}


	/**
	 * @param array $handler
	 * @return mixed
	 * @throws Exception
	 */
	public function execute(array $handler): mixed
	{
		$rpc = Snowflake::app()->getRpc();
		$rpc->addProducer($this->cmd, $handler, $this->config);

		return parent::execute($handler);
	}


}
