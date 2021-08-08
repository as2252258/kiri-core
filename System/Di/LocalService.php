<?php

namespace Snowflake\Di;

use Annotation\Inject;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;


/**
 * 服务定位器
 */
class LocalService extends Component
{


    private array $_components = [];


    private array $_definition = [];


    /**
     * @param $name
     * @param $define
     */
    public function set($name, $define)
    {
        unset($this->_components[$name]);

        $this->_definition[$name] = $define;
    }


    /**
     * @throws \Exception
     */
    public function get(string $name, $throwException = true)
    {
        if (isset($this->_components[$name])) {
            return $this->_components[$name];
        }
        if (isset($this->_definition[$name])) {
            $definition = $this->_definition[$name];
            if (is_object($definition) && !$definition instanceof \Closure) {
                return $this->_components[$name] = $definition;
            }
            return $this->_components[$name] = Snowflake::createObject($definition);
        } else if ($throwException) {
            throw new \Exception("Unknown component ID: $name");
        }
        return null;
    }


    /**
     * @param array $components
     */
    public function setComponents(array $components)
    {
        foreach ($components as $name => $component) {
            $this->set($name, $component);
        }
    }


    /**
     * @param $id
     * @return bool
     */
    public function has($id): bool
    {
        return isset($this->_components[$id]) || isset($this->_definition[$id]);
    }


    /**
     * @param $id
     */
    public function remove($id): void
    {
        unset($this->_components[$id], $this->_definition[$id]);
    }


}
