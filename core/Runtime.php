<?php


namespace Kiri;


use Exception;
use Kiri\Abstracts\Input;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Runtime
 * @package Kiri
 */
class Runtime extends Command
{


	public string $command = 'runtime:builder';

	public string $description = 'create app file cache';


	const CACHE_NAME = '.runtime.cache';
	const CONFIG_NAME = '.config.cache';


	protected function configure()
	{
		$this->setName('runtime:builder');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return string
	 * @throws Exception
	 */
	public function execute(InputInterface $input, OutputInterface $output): int
	{
		// TODO: Implement onHandler() method.
		$annotation = Kiri::app()->getAnnotation();

		$runtime = storage(static::CACHE_NAME);
		$config = storage(static::CONFIG_NAME);

		Kiri::writeFile($config, $this->configEach());
		Kiri::writeFile($runtime, serialize($annotation->getLoader()));

		return 1;
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function configEach(): string
	{
		$array = [];
		$configs = Kiri::app()->getConfig();
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
		return serialize($array);
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
