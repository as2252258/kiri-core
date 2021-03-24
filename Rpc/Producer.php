<?php


namespace Rpc;


use Exception;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * Class Producer
 * @package Rpc
 */
class Producer extends Component
{

	private array $producers = [];


	private array $classAlias = [];


	private array $consumers = [];


	private array $cods = [];


	/**
	 * @param string $name
	 * @param array $handler
	 * @param array $node
	 */
	public function addProducer(string $name, array $handler, array $node)
	{
		$this->classAlias[get_class($handler[0])] = $name;

		$this->consumers[$name] = $handler[0];

		$this->producers[$name] = $node;
	}


	/**
	 * @param string $cmd
	 * @param array $handler
	 */
	public function addConsumer(string $cmd, array $handler)
	{
		$class = get_class($handler[0]);

		if (!isset($this->classAlias[$class])) {
			return;
		}

		$name = $this->classAlias[$class];

		$this->cods[$name . '.' . $cmd] = $handler;
	}


	/**
	 * @param $cmd
	 * @param mixed ...$params
	 * @return mixed
	 */
	public function dispatch($cmd, mixed ...$params): mixed
	{
		$handler = $this->cods[$cmd] ?? null;
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
		if (!isset($this->consumers[$name])) {
			throw new Exception('Unknown rpc client.');
		}
		return $this->consumers[$name];
	}


	/**
	 * @return array
	 */
	public function getService(): array
	{
		$array = [];
		foreach (array_keys($this->cods) as $key) {
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
	 * @throws ComponentException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function getClient(string $name, string $host = null): Client
	{
		$producer = $this->producers[$name] ?? null;
		if ($producer === null) {
			throw new Exception('Unknown rpc client config.');
		}
		if (!empty($host)) {
			$producer['host'] = $host;
		} else if (!isset($producer['host'])) {
			$producer['host'] = Snowflake::localhost();
		}
		$producerName = $this->getName($name, $producer);

		$snowflake = Snowflake::app();
		if (!$snowflake->has($producerName)) {
			return $snowflake->set($producerName, $this->definer($name, $producer));
		} else {
			return $snowflake->get($producerName);
		}
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
