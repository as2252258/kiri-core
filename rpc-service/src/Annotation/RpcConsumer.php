<?php


namespace Annotation\Rpc;


use Annotation\Attribute;
use Exception;
use Kiri\Kiri;


/**
 * Class RpcClient
 * @package Annotation\Rpc
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class RpcConsumer extends Attribute
{

	/**
	 * RpcClient constructor.
	 * @param string $package
	 * @param string $method
	 * @param int $timeout
	 * @param int $mode
	 */
    public function __construct(
        public string $package,
        public string $method,
        public int $timeout,
        public int $mode
    )
    {
    }


	/**
	 * @param mixed $class
	 * @param mixed $method
	 * @return bool
	 * @throws Exception
	 */
    public function execute(mixed $class, mixed $method = ''): bool
    {
        return true;
    }


}
