<?php

namespace Kiri\Abstracts;

use Exception;

class LocalService extends Component implements LocalServiceInterface
{


    /**
     * @var array
     */
    protected array $_definition = [];


    /**
     * @var array
     */
    protected array $_components = [];


    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->_definition[$name]) || isset($this->_components[$name]);
    }


    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function get(string $name): mixed
    {
        if (isset($this->_components[$name])) return $this->_components[$name];
        if (!isset($this->_definition[$name])) {
            throw new Exception('Undefined component ' . $name);
        }
        $definition = $this->_definition[$name];
        if (!($definition instanceof \Closure)) {
            $this->_components[$name] = \Kiri::createObject($definition);
        } else {
            $this->_components[$name] = call_user_func($definition);
        }
        return $this->_components[$name];
    }


    /**
     * @param string $name
     * @param array $value
     * @return void
     */
    public function set(string $name, array $value): void
    {
        $this->_definition[$name] = $value;
        unset($this->_components[$name]);
    }

}