<?php

namespace Http\Handler;

use Annotation\Aspect;
use Closure;
use Exception;
use Psr\Http\Server\MiddlewareInterface;
use Kiri\Di\NoteManager;
use Kiri\IAspect;
use Kiri\Kiri;
use ReflectionException;
use Throwable;

class Pipeline
{
	protected mixed $passable;

	protected mixed $overall;

	protected mixed $pipes = [];


	protected mixed $pipeline;

	protected mixed $exceptionHandler;

	/**
	 * 初始数据
	 * @param $passable
	 * @return $this
	 */
	public function send($passable): static
	{
		$this->passable = $passable;
		return $this;
	}


	/**
	 * @param $middle
	 * @return $this
	 */
	public function overall($middle): static
	{
		$this->overall = $middle;
		return $this;
	}


	/**
	 * 调用栈
	 * @param $pipes
	 * @return $this
	 */
	public function through($pipes): static
	{
		if (empty($pipes)) return $this;
		if (empty($this->pipes)) {
			$this->pipes = is_array($pipes) ? $pipes : func_get_args();
		} else {
			foreach ($pipes as $pipe) {
				$this->pipes[] = $pipe;
			}
		}
		return $this;
	}

	/**
	 * 执行
	 * @param callable $destination
	 * @return static
	 */
	public function then(callable $destination): static
	{
		$parameters = $this->passable;
		if (!empty($this->overall)) {
			array_unshift($this->pipes, $this->overall);
		}
		if (is_array($destination)) {
			$destination = $this->aspect_caller($destination, $parameters);
		}
		$this->pipeline = array_reduce(array_reverse($this->pipes), $this->carry(),
			static function () use ($destination, $parameters) {
				return call_user_func($destination, ...$parameters);
			}
		);
		return $this->clear();
	}


	/**
	 * @return $this
	 */
	private function clear(): static
	{
		$this->pipes = [];
		$this->passable = null;
		$this->overall = null;
		return $this;
	}


	/**
	 * @param $destination
	 * @param $parameters
	 * @return Closure|array
     */
	private function aspect_caller($destination, $parameters): Closure|array
	{
		[$controller, $action] = $destination;
		/** @var Aspect $aop */
		$aop = NoteManager::getSpecify_annotation(Aspect::class, $controller::class, $action);
		if (!empty($aop)) {
			$aop = Kiri::getDi()->get($aop->aspect);
			$destination = static function () use ($aop, $destination, $parameters) {
				/** @var IAspect $aop */
				$aop->before();
				$aop->after($data = $aop->invoke($destination, $parameters));
				return $data;
			};
		}
		return $destination;
	}


	/**
	 * @param $request
	 * @return mixed
	 */
	public function interpreter($request): mixed
	{
		return call_user_func($this->pipeline, $request);
	}


	/**
	 * 设置异常处理器
	 * @param callable $handler
	 * @return $this
	 */
	public function whenException(callable $handler): static
	{
		$this->exceptionHandler = $handler;
		return $this;
	}


	/**
	 * @return Closure
	 */
	protected function carry(): Closure
	{
		return static function ($stack, $pipe) {
			return static function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof MiddlewareInterface) {
					$pipe = [$pipe, 'process'];
				}
				return $pipe($passable, $stack);
			};
		};
	}

	/**
	 * 异常处理
	 * @param $passable
	 * @param Throwable $e
	 * @return mixed
	 * @throws Throwable
	 */
	protected function handleException($passable, Throwable $e): mixed
	{
		if ($this->exceptionHandler) {
			return call_user_func($this->exceptionHandler, $passable, $e);
		}
		throw $e;
	}

}
