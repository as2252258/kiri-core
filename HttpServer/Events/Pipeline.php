<?php


namespace HttpServer\Events;


class Pipeline
{

    private ?\Closure $_if = null;
    private ?\Closure $_else = null;
    private ?\Closure $_catch = null;
    private ?\Closure $_after = null;
    private ?\Closure $_before = null;

    private bool $condition;


    /**
     * @param bool $condition
     * @param \Closure $handler
     * @return $this
     */
    public function if(bool $condition, \Closure $handler): static
    {
        $this->condition = $condition;
        $this->_if = $handler;
        return $this;
    }


    /**
     * @param \Closure $handler
     * @return $this
     */
    public function else(\Closure $handler): static
    {
        $this->_else = $handler;
        return $this;
    }


    /**
     * @param \Closure $handler
     * @return $this
     */
    public function catch(\Closure $handler): static
    {
        $this->_catch = $handler;
        return $this;
    }

    /**
     * @param \Closure $handler
     * @return $this
     */
    public function after(\Closure $handler): static
    {
        $this->_after = $handler;
        return $this;
    }

    /**
     * @param \Closure $handler
     * @return $this
     */
    public function before(\Closure $handler): static
    {
        $this->_before = $handler;
        return $this;
    }


    /**
     * @param $argv
     * @return mixed
     */
    public function exec(...$argv)
    {
        try {
            if ($this->_before instanceof \Closure) {
                call_user_func($this->_before, ...$argv);
            }
            if ($this->condition !== true) {
                call_user_func($this->_else, ...$argv);
            } else {
                call_user_func($this->_if, ...$argv);
            }
            return $argv;
        } catch (\Throwable $exception) {
            call_user_func($this->_catch, $exception);
            return $argv;
        } finally {
            if ($this->_after instanceof \Closure) {
                call_user_func($this->_after, ...$argv);
            }
        }
    }

}
