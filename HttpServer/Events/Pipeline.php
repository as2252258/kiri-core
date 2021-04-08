<?php


namespace HttpServer\Events;


class Pipeline
{

    private \Closure $_if;
    private \Closure $_else;
    private \Closure $_catch;

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
     * @param $argv
     * @return mixed
     */
    public function exec(...$argv)
    {
        try {
            if ($this->condition !== true) {
                call_user_func($this->_else, ...$argv);
            } else {
                call_user_func($this->_if, ...$argv);
            }
            return $argv;
        } catch (\Throwable $exception) {
            call_user_func($this->_catch, $exception);
            return $argv;
        }
    }

}
