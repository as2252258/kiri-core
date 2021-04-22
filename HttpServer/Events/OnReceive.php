<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Request;
use HttpServer\Route\Router;
use ReflectionException;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnReceive
 * @package HttpServer\Events
 */
class OnReceive extends Callback
{

	public int $port = 0;


	public string $host = '';


	private Router $router;


	public function init()
	{
		$this->router = Snowflake::app()->getRouter();
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reID
	 * @param string $data
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $fd, int $reID, string $data): mixed
	{
		try {
			defer(function (){
				fire(Event::SYSTEM_RESOURCE_RELEASES);
			});
			$request = $this->_request($fd, $data, $reID);
			if (($node = $this->router->find_path($request)) === null) {
				return $server->send($fd, Json::encode(['state' => 404]));
			}
			$dispatch = $node->dispatch();
			if (!is_string($dispatch)) $dispatch = Json::encode($dispatch);
			if (empty($dispatch)) {
				$dispatch = Json::encode(['state' => 0, 'message' => 'ok']);
			}
			return $server->send($fd, $dispatch);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'receive');
			$error = ['state' => 500, 'message' => $exception->getMessage()];
			return $server->send($fd, Json::encode($error));
		}
	}


}
