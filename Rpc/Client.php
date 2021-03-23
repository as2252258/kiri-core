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

    private array $config = [];


    private CClient $client;


    /**
     * @param $name
     */
    public function setConfig($name)
    {
        $this->config = $name;
    }


	/**
	 * @param string $route
	 * @param array $param
	 * @return mixed
	 * @throws Exception
	 */
    public function request(string $route, array $param): mixed
    {
        $service = $this->config;
        if (empty($service)) {
            return null;
        }
        if (!($this->client instanceof CClient)) {
	        $this->client = $this->getClient();
        }
        if (!$this->client->isConnected()) {
            if (!$this->client->connect($service['host'], $service['port'], $service['timeout'])) {
                return $this->client->errCode . ':' . $this->client->errMsg;
            }
        }
        $isSend = $this->client->send(serialize(['route' => $route, 'body' => $param]));
        if ($isSend === false) {
            return $this->client->errCode . ':' . $this->client->errMsg;
        }
        return unserialize($this->client->recv());
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function getClient(): CClient
    {
        return objectPool(CClient::class, function () {
            $client = new CClient(SWOOLE_SOCK_TCP6);
            $client->set([
                'timeout'            => 0.5,
                'connect_timeout'    => 1.0,
                'write_timeout'      => 10.0,
                'read_timeout'       => 0.5,
                'open_tcp_keepalive' => true,
            ]);
        });
    }


}
