<?php


namespace Snowflake;


use Console\Command;
use Exception;
use Snowflake\Abstracts\Input;


/**
 * Class Runtime
 * @package Snowflake
 */
class Runtime extends Command
{


	public string $command = 'runtime:builder';

	public string $description = 'create app file cache';


	const CACHE_NAME = '.runtime.cache';
	const CONFIG_NAME = '.config.cache';


	/**
	 * @param Input $dtl
	 * @throws Exception
	 */
	public function onHandler(Input $dtl)
	{
		// TODO: Implement onHandler() method.
		$annotation = Snowflake::app()->getAnnotation();

		$runtime = storage(static::CACHE_NAME);

		$configs = $this->configEach();

		Snowflake::writeFile(storage(static::CONFIG_NAME), serialize($configs));
		Snowflake::writeFile($runtime, serialize($annotation->getLoader()));
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	public function configEach(): array
	{
		$array = [];
		$configs = Snowflake::app()->getConfig();
		foreach ($configs->getData() as $key => $datum) {
			if ($datum instanceof \Closure) {
				continue;
			}
			if (is_array($datum)) {
				$array[$key] = $this->arrayEach($datum);
			} else {
				$array[$key] = $datum;
			}
		}
		var_dump($array);
		return $array;
	}


	/**
	 * @param array $value
	 * @return array
	 */
	private function arrayEach(array $value): array
	{
		$array = [];
		foreach ($value as $key => $item) {
			if ($item instanceof \Closure) {
				continue;
			}
			if (is_array($item)) {
				$array[$key] = $this->arrayEach($item);
			} else {
				$array[$key] = $item;
			}
		}
		return $array;
	}


}
