<?php

namespace Http\Route;

use Annotation\Aspect;
use Closure;
use Http\IInterface\MiddlewareInterface;
use Kiri\Di\NoteManager;
use Kiri\Kiri;
use Throwable;

class Pipeline
{
    protected $passable;

    protected $overall;

    protected $pipes = [];


    protected $pipeline;

    protected $exceptionHandler;

    /**
     * 初始数据
     * @param $passable
     * @return $this
     */
    public function send($passable)
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
    public function through($pipes)
    {
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
     * @param Closure $destination
     * @return mixed
     */
    public function then(callable $destination)
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
    private function clear()
    {
        $this->pipes = [];
        $this->passable = null;
        $this->overall = null;
        return $this;
    }


    /**
     * @param $destination
     * @param $parameters
     * @return \Closure
     */
    private function aspect_caller($destination, $parameters)
    {
        [$controller, $action] = $destination;
        /** @var \Annotation\Aspect $aop */
        $aop = NoteManager::getSpecify_annotation(Aspect::class, $controller::class, $action);
        if (!empty($aop)) {
            $aop = Kiri::getDi()->get($aop->aspect);
            $destination = static function () use ($aop, $destination, $parameters) {
                /** @var \Kiri\IAspect $aop */
                $aop->invoke($destination, $parameters);
            };
        }
        return $destination;
    }


    /**
     * @return mixed
     */
    public function interpreter(): mixed
    {
        return call_user_func($this->pipeline, request());
    }


    /**
     * 设置异常处理器
     * @param callable $handler
     * @return $this
     */
    public function whenException($handler)
    {
        $this->exceptionHandler = $handler;
        return $this;
    }


    /**
     * @return \Closure
     */
    protected function carry(): Closure
    {
        return static function ($stack, $pipe) {
            return static function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof MiddlewareInterface) {
                    $pipe = [$pipe, 'OnHandler'];
                }
                return $pipe($passable, $stack);
            };
        };
    }

    /**
     * 异常处理
     * @param $passable
     * @param $e
     * @return mixed
     * @throws \Throwable
     */
    protected function handleException($passable, Throwable $e)
    {
        if ($this->exceptionHandler) {
            return call_user_func($this->exceptionHandler, $passable, $e);
        }
        throw $e;
    }

}
