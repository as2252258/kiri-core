<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Http\Route\MiddlewareManager;
use ReflectionException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use Http\IInterface\MiddlewareInterface as IMiddleware;

/**
 * Class Middleware
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Middleware extends Attribute
{


    /**
     * Interceptor constructor.
     * @param string|array $middleware
     * @throws
     */
    public function __construct(public string|array $middleware)
    {
        if (is_string($this->middleware)) {
            $this->middleware = [$this->middleware];
        }

        $array = [];
        foreach ($this->middleware as $value) {
            $sn = di($value);
            if (!($sn instanceof IMiddleware)) {
                continue;
            }
            $array[] = [$sn, 'onHandler'];
        }
        $this->middleware = $array;
    }


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return $this
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
    public function execute(mixed $class, mixed $method = null): static
    {
        $middleware = Kiri::getDi()->get(MiddlewareManager::class);
        $middleware->addMiddlewares($class, $method, $this->middleware);
        return $this;
    }


}
