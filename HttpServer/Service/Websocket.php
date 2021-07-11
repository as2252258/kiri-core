<?php
declare(strict_types=1);

namespace HttpServer\Service;


use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use HttpServer\Service\Abstracts\Websocket as HAWebsocket;


/**
 * Class Websocket
 * @package HttpServer\Service
 */
class Websocket extends HAWebsocket
{

    /**
     * @throws ReflectionException
     * @throws NotFindClassException
     */
    public function onInit()
    {
        $this->onHandlerListener();
        $this->onBaseListener();
    }


    /**
     * @throws NotFindClassException
     * @throws ReflectionException
     */
    public function onBaseListener()
    {
        $this->on('connect', function () {});
        $this->on('handshake', $this->createHandler('handshake'));
        $this->on('message', $this->createHandler('message'));
        $this->on('close', $this->createHandler('close'));
    }

}
