<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Request;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Swoole\Server;

/**
 * Class OnReceive
 * @package HttpServer\Events
 */
class OnReceive extends Callback
{

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
			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

			$client = $server->getClientInfo($fd, $reID);
			$name = $this->getName($client, Event::SERVER_RECEIVE);

			if (Config::get('rpc.port', 0) == $client['server_port']) {
				$result = router()->find_path(Request::rpcRequest($fd, $data, $reID))?->dispatch();
			} else {
				$result = Event::trigger($name, [$server, $data, $client]);
			}
			if (is_array($result) || is_object($result)) {
				$result = Json::encode($result);
			}
		} catch (\Throwable $exception) {
			$result = logger()->exception($exception);
		} finally {
			return $server->send($fd, $result);
		}
	}


}
