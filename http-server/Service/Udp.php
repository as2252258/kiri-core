<?php

namespace Server\Service;



use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Abstracts\Server;
use Server\Abstracts\Utility\EventDispatchHelper;
use Server\Abstracts\Utility\ResponseHelper;
use Server\Constrict\UdpEmitter;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\SInterface\OnPacketInterface;


/**
 *
 */
class Udp implements OnPacketInterface
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
        $exceptionHandler = Config::get('exception.udp', ExceptionHandlerDispatcher::class);
        if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
            $exceptionHandler = ExceptionHandlerDispatcher::class;
        }
        $this->exceptionHandler = Kiri::getDi()->get($exceptionHandler);
        $this->responseEmitter = Kiri::getDi()->get(UdpEmitter::class);
    }



    /**
	 * @param Server $server
	 * @param string $data
	 * @param array $clientInfo
	 */
	public function onPacket(Server $server, string $data, array $clientInfo): void
	{
		// TODO: Implement onPacket() method.
	}

}
