<?php


namespace Rpc;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Core\Json;
use Swoole\Coroutine\Client as CClient;


/**
 * Class Client
 * @package Rpc
 */
class Client extends Component
{

	public array $config = [];


	public string $service = '';


	private ?CClient $client = null;


	/**
	 * @param string $cmd
	 * @param array $param
	 * @return mixed
	 * @throws Exception
	 */
	public function dispatch(string $cmd, array $param): mixed
	{
		if (empty($this->config)) {
			return $this->addError('Related service not found(404)');
		}
		if (!($this->client instanceof CClient)) {
			$this->client = $this->getClient();
		}
		if (!$this->client->isConnected()) {
			$settings = [$this->config['host'], $this->config['port'], $this->config['timeout'] ?? 1];
			if (!$this->client->connect(...$settings)) {
				return $this->addError($this->client->errMsg . '(' . $this->client->errCode . ')');
			}
		}
		$isSend = $this->client->send(Json::encode(['cmd' => $cmd, 'body' => $param]));
		if ($isSend === false) {
			return $this->addError($this->client->errMsg . '(' . $this->client->errCode . ')');
		}

		if (is_bool($unpack = Json::decode($this->client->recv()))) {
			return $this->addError('Service return data format error(500)');
		}
		return $unpack;
	}


	/**
	 * 断开链接
	 */
	public function close()
	{
		if (!$this->client || !$this->client->isConnected()) {
			return;
		}
		$this->client->close();
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getClient(): CClient
	{
		return objectPool(CClient::class, function () {
			$client = new CClient($this->config['mode'] ?? SWOOLE_SOCK_TCP);
			$client->set([
				'timeout'            => 0.5,
				'connect_timeout'    => 1.0,
				'write_timeout'      => 10.0,
				'read_timeout'       => 0.5,
				'open_tcp_keepalive' => true,
			]);
			return $client;
		});
	}


}
