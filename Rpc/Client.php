<?php


namespace Rpc;


use Exception;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Channel;
use Snowflake\Core\Json;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
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
		if (!$this->client->isConnected() && !$this->connect()) {
			return false;
		}
		$isSend = $this->client->send(Json::encode(['cmd' => $cmd, 'body' => $param]));
		$this->recover();
		if ($isSend === false) {
			return $this->addError($this->client->errMsg . '(' . $this->client->errCode . ')');
		}
		if (is_bool($unpack = Json::decode($this->client->recv()))) {
			return $this->addError('Service return data format error(500)');
		}
		return $unpack;
	}


	/**
	 * @throws ReflectionException
	 * @throws ComponentException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function recover(): static
	{
		/** @var Channel $channel */
		$channel = Snowflake::app()->get('channel');
		$channel->push($this->client, CClient::class);
		$this->client = null;
		return $this;
	}


	/**
	 * @return bool
	 * @throws Exception
	 */
	private function connect(): bool
	{
		$host = $this->config['host'] ?? '127.0.0.1';
		if (!isset($this->config['port'])) {
			return $this->addError('Related service not have port(404)');
		}
		$timeout = $this->config['timeout'] ?? 0.2;
		if (!$this->client->connect($host, $this->config['port'], $timeout)) {
			return $this->addError($this->client->errMsg . '(' . $this->client->errCode . ')');
		}
		return true;
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
		/** @var Channel $channel */
		$channel = Snowflake::app()->get('channel');
		return $channel->pop(CClient::class, function () {
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
