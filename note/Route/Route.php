<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Http\Handler\Abstracts\HandlerManager;
use Http\Handler\Handler;
use Http\Route\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)] class Route extends Attribute
{

	/**
	 * Route constructor.
	 * @param string $uri
	 * @param string $method
	 * @param string $version
	 */
	public function __construct(public string $uri, public string $method, public string $version = 'v.1.0')
	{
		$this->uri = '/' . ltrim($this->uri, '/');
		$this->method = strtoupper($this->method);
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return Router
	 * @throws \ReflectionException
	 */
	public function execute(mixed $class, mixed $method = null): Router
	{
		HandlerManager::add($this->uri, $this->method, new Handler($this->uri, [$class, $method]));
		return parent::execute($class, $method);
	}


}
