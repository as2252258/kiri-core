<?php


namespace Rpc;


use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Kiri\Abstracts\Component;
use Kiri\Kiri;


/**
 * Class Producer
 * @package Rpc
 */
class Producer extends Component
{

	private static array $producers = [];


	private static array $classAlias = [];


	private static array $consumers = [];


	private static array $cods = [];


	/**
	 * @param string $name
	 * @param array $handler
	 * @param array $node
	 */
	public function addProducer(string $name, array $handler, array $node)
	{
		static::$classAlias[$handler[0]::class] = $name;

		static::$consumers[$name] = $handler[0];

		static::$producers[$name] = $node;
	}


	/**
	 * @param string $cmd
	 * @param array $handler
	 */
	public function addConsumer(string $cmd, array $handler)
	{
		$class = $handler[0]::class;

		if (!isset(static::$classAlias[$class])) {
			return;
		}

		$name = static::$classAlias[$class];

		static::$cods[$name . '.' . $cmd] = $handler;
	}


	/**
	 * @param $cmd
	 * @param mixed ...$params
	 * @return mixed
	 */
	public function dispatch($cmd, mixed ...$params): mixed
	{
		$handler = static::$cods[$cmd] ?? null;
		if (empty($handler)) {
			return false;
		}
		return call_user_func($handler, ...$params);
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function get($name): mixed
	{
		if (!isset(static::$consumers[$name])) {
			throw new Exception('Unknown rpc client.');
		}
		return static::$consumers[$name];
	}


	/**
	 * @return array
	 */
	public function getService(): array
	{
		$array = [];
		foreach (array_keys(static::$cods) as $key) {
			$explode = explode('.', $key);
			$prefix = array_shift($explode);

			$explode = implode('.', $explode);
			if (!isset($array[$prefix])) {
				$array[$prefix] = [];
			}
			$array[$prefix][] = $explode;
		}
		return $array;
	}


	/**
	 * @param string $name
	 * @param string|null $host
	 * @return mixed
	 * @throws Exception
	 */
	public function getClient(string $name, string $host = null): Client
	{
		$producer = static::$producers[$name] ?? null;
		if ($producer === null) {
			throw new Exception('Unknown rpc client config.');
		}
		if (!empty($host)) {
			$producer['host'] = $host;
		} else if (!isset($producer['host'])) {
			$producer['host'] = Kiri::localhost();
		}
		$producerName = $this->getName($name, $producer);

		$snowflake = Kiri::app();
		if (!$snowflake->has($producerName)) {
			return $snowflake->set($producerName, $this->definer($name, $producer));
		} else {
			return $snowflake->get($producerName);
		}
	}


	/**
	 * @param string $name
	 * @param string|null $host
	 * @return Client
	 * @throws Exception
	 */
	public function consumer(string $name, string $host = null): Client
	{
		return $this->getClient($name, $host);
	}


	/**
	 * @param $name
	 * @param $producer
	 * @return array
	 */
	#[ArrayShape(['class' => "string", 'service' => "", 'config' => ""])]
	private function definer($name, $producer): array
	{
		return ['class' => Client::class, 'service' => $name, 'config' => $producer];
	}


	/**
	 * @param $name
	 * @return Client|bool
	 * @throws Exception
	 */
	public function __get($name): Client|bool
	{
		return $this->get($name); // TODO: Change the autogenerated stub
	}



	/**
	 * @param $name
	 * @param $config
	 * @return string
	 */
	private function getName($name, $config): string
	{
		return 'rpc.client.' . $name . '.' . $config['host'];
	}

}
