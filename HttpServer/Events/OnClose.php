<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Request;
use HttpServer\Route\Node;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Server;
use Exception;
use Swoole\WebSocket\Server as WServer;

/**
 * Class OnClose
 * @package HttpServer\Events
 *
 */
class OnClose extends Callback
{


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $fd)
	{
		$this->execute($server, $fd);
		fire(Event::SYSTEM_RESOURCE_RELEASES);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function execute(Server $server, int $fd): void
	{
		try {
			$this->loadNode($server, $fd);
		} catch (\Throwable $exception) {
			$this->addError($exception);
		} finally {
			$logger = Snowflake::app()->getLogger();
			$logger->insert();
		}
	}


	/**
	 * @param $server
	 * @param $fd
	 * @return mixed
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	private function loadNode($server, $fd): mixed
	{
		$query = Request::socketQuery((object)['fd' => $fd], Socket::CLOSE);
		if (($node = router()->find_path($query)) !== null) {
			return $node->dispatch($server, $fd);
		}
		return null;
	}
}
