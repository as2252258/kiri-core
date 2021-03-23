<?php


namespace Rpc;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;
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
			return null;
		}
		if (!($this->client instanceof CClient)) {
			$this->client = $this->getClient();
		}
		if (!$this->client->isConnected()) {
			if (!$this->client->connect(
				$this->config['host'], $this->config['port'],
				$this->config['timeout'] ?? 1
			)) {
				return $this->client->errCode . ':' . $this->client->errMsg;
			}
		}
		$isSend = $this->client->send(serialize(['cmd' => $cmd, 'body' => $param]));
		if ($isSend === false) {
			return $this->client->errCode . ':' . $this->client->errMsg;
		}
		return unserialize($this->client->recv());
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
