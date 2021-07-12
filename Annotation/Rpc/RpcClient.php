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
	 * @param mixed $class
	 * @param mixed $method
	 * @return bool
	 * @throws Exception
	 */
    public function execute(mixed $class, mixed $method = ''): bool
    {
        $rpc = Snowflake::app()->getRpc();
        $rpc->addProducer($this->cmd, [$class, $method], $this->config);

        return true;
    }


}
