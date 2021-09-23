<?php

namespace Server\Service;

use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Abstracts\Utility\EventDispatchHelper;
use Server\Abstracts\Utility\ResponseHelper;
use Server\Constrict\TcpEmitter;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\SInterface\OnCloseInterface;
use Server\SInterface\OnConnectInterface;
use Server\SInterface\OnReceiveInterface;
use Swoole\Server;


/**
 *
 */
class Tcp implements OnConnectInterface, OnCloseInterface, OnReceiveInterface
{

    use EventDispatchHelper;
    use ResponseHelper;


    /**
     * @var ExceptionHandlerInterface
     */
    public ExceptionHandlerInterface $exceptionHandler;


    /**
     * @throws ConfigException
     */
    public function init()
    {
        $exceptionHandler = Config::get('exception.tcp', ExceptionHandlerDispatcher::class);
        if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
            $exceptionHandler = ExceptionHandlerDispatcher::class;
        }
        $this->exceptionHandler = Kiri::getDi()->get($exceptionHandler);
        $this->responseEmitter = Kiri::getDi()->get(TcpEmitter::class);
    }



    /**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onConnect(Server $server, int $fd): void
	{
		// TODO: Implement onConnect() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data): void
	{
		// TODO: Implement onReceive() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd): void
	{
		// TODO: Implement onClose() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onDisconnect(Server $server, int $fd): void
	{
		// TODO: Implement onDisconnect() method.
	}
}
