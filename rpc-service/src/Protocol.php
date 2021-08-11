<?php

namespace Rpc;

use Exception;
use JetBrains\PhpStorm\Pure;
use Rpc\Annotation\RpcService;


/**
 *
 */
class Protocol
{

	private string $version = 'v1.0';


	private array $headers = [];


	private mixed $data = [];

	/**
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * @return mixed
	 */
	public function getData(): mixed
	{
		return $this->data;
	}


	/**
	 * @throws Exception
	 */
	public static function parse($data): static
	{
		$protocol = new Protocol();
		$protocol->parseHeaders(...explode("\r\n\r\n", $data));
		return $protocol;
	}


	/**
	 * @param string $service
	 * @param string $cmd
	 * @param array $param
	 * @return string
	 */
	public static function encode(string $service, string $cmd, array $param = []): string
	{
		$proto = 'REQUEST tcp/other.protocol v1.0' . "\r\n";
		$proto .= ':Source: ' . implode(',', swoole_get_local_ip()) . "\r\n";
		$proto .= ':Package: ' . $service . "\r\n";
		$proto .= ':Path: ' . $cmd . "\r\n";
		$proto .= ':Content-Type: ' . $cmd . "\r\n";
		$proto .= ':Method: json-rpc' . "\r\n\r\n";

		return $proto . json_encode($param) . "\r\n\r\n";
	}


	/**
	 * @param string $body
	 * @return void
	 */
	private function parseBody(string $body): void
	{
		if ($this->headers['Content-Type'] == RpcService::PROTOCOL_JSON) {
			$this->data = json_decode($body, true);
		} else {
			$this->data = unserialize($body);
		}
	}


	/**
	 * @param string $headers
	 * @param string $body
	 * @return void
	 * @throws Exception
	 */
	private function parseHeaders(string $headers, string $body): void
	{
		$explode = explode("\r\n", $headers);
		$this->headers = [];
		foreach ($explode as $key => $value) {
			if ($key == 0) {
				if (!str_starts_with($value, 'REQUEST tcp/other.protocol')) {
					throw new Exception('Protocol format error.');
				}
				$this->version = str_replace('REQUEST tcp/other.protocol ', '', $value);
				continue;
			}
			[$name, $item] = explode(': ', $value);
			$this->headers[str_replace(':', '', $name)] = $item;
		}
		if (count(array_diff_key(Service::A_DEFAULT, $this->headers)) > 0) {
			throw new Exception('Protocol format error.');
		}
		$this->parseBody($body);
	}


	/**
	 * @return string
	 */
	#[Pure] public function parseUrl(): string
	{
		return ':rpc/' . $this->headers['Package'] . '/' . $this->headers['Path'] . '/' . $this->getVersion();
	}

}
