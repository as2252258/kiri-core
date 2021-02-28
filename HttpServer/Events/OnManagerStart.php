<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Server;


/**
 * Class OnManagerStart
 * @package HttpServer\Events
 */
class OnManagerStart extends Callback
{


    /** @var int|string 重启信号 */
    private int $signal = SIGUSR1 | SIGUSR2;

    /** @var bool 是否打印 */
    private bool $isPrint = false;


    /**
     * @param Server $server
     * @throws Exception
     */
    public function onHandler(Server $server)
    {
        Snowflake::setWorkerId($server->manager_pid);

        $events = Snowflake::app()->getEvent();
        $events->trigger(Event::SERVER_MANAGER_START, [$server]);

        if (!Snowflake::isLinux()) {
            return;
        }
        name(Config::get('id', false, 'system') . ' Server Manager.');
    }


}
