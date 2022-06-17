<?php

namespace Kiri\Grpc;

use Google\Protobuf\Internal\GPBUtil;
use GPBMetadata\UserService;
use Kiri\Abstracts\Component;
use Kiri\Di\ContainerInterface;
use Swoole\Server;

class GrpcServer extends Component
{


	/**
	 * @param ContainerInterface $container
	 * @param array $config
	 * @throws \Exception
	 */
	public function __construct(
		public ContainerInterface $container,
		array                     $config = [])
	{
		parent::__construct($config);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 * @return void
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data): void
	{
		UserService::initOnce();

	}


}
