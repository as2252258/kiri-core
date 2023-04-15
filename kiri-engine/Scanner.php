<?php

namespace Kiri;

use Exception;
use Kiri\Abstracts\Component;
use Kiri\Di\Container;
use ReflectionException;

class Scanner extends Component
{


	private array $files = [];


	/**
	 * @param string $path
	 * @return void
	 */
	public function read(string $path): void
	{
		$this->load_dir($path);
	}


	/**
	 * @param string $namespace
	 * @return void
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function parse(string $namespace): void
	{
		$container = Container::instance();
		foreach ($this->files as $file) {
			$class = $namespace . '\\' . $this->rename($file);
			if (!file_exists($class)) {
				throw new Exception('Please follow the PSR-4 specification to write code.' . $class);
			}
			$container->parse($class);
		}
	}


	/**
	 * @param string $file
	 * @return string
	 */
	private function rename(string $file): string
	{
		$filter = array_filter(explode('/', $file), function ($value) {
			if (empty($value)) {
				return false;
			}
			return ucfirst($value);
		});
		array_shift($filter);
		return implode('\\', $filter);
	}


	/**
	 * @param string $path
	 * @return void
	 */
	private function load_dir(string $path): void
	{
		$dir = new \DirectoryIterator($path);
		foreach ($dir as $value) {
			if ($value->isDot()) {
				continue;
			}
			if (is_dir($value)) {
				$this->load_dir($value->getRealPath());
			} else if ($value->getExtension() == '.php') {
				$this->load_file($value);
			}
		}
	}


	/**
	 * @param string $path
	 * @return void
	 */
	private function load_file(string $path): void
	{
		try {
			require_once "$path";
			$path = str_replace($_SERVER['HOME'], '', $path);
			$path = str_replace('.php', '', $path);
			$this->files[] = $path;
		} catch (\Throwable $throwable) {
			error($throwable->getMessage(), [$throwable]);
		}
	}


}
