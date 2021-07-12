<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:29
 */
declare(strict_types=1);

namespace Snowflake\Di;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class Service
 * @package Snowflake\Snowflake\Di
 */
class Service extends Component
{

    private array $_components = [];


    private array $_definition = [];


    private array $_ids = [];


    protected array $_alias = [];

    /**
     * @param $id
     * @param bool $try
     * @return mixed
     * @throws Exception
     */
    public function get($id, bool $try = true): mixed
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }
        if (!isset($this->_definition[$id]) && !isset($this->_alias[$id])) {
            if ($try === false) {
                return null;
            }
            throw new ComponentException("Unknown component ID: $id");
        }
        if (isset($this->_definition[$id])) {
            $config = $this->_definition[$id];
            if (is_object($config)) {
                return $this->_components[$id] = $config;
            }
        } else {
            $config = $this->_alias[$id];
        }
	    return $this->_components[$id] = Snowflake::createObject($config);
    }

    /**
     * @param string $className
     * @param string $alias
     */
    public function setAlias(string $className, string $alias)
    {
        $this->_alias[$className] = $alias;
    }

    /**
     * @param $id
     * @param $definition
     *
     * @return mixed
     * @throws Exception
     */
    public function set($id, $definition): void
    {
        if ($definition === NULL) {
            $this->remove($id);
            return;
        }

        $this->_ids[] = $id;

        unset($this->_components[$id]);
        if (is_object($definition) || is_callable($definition, TRUE)) {
            $this->_definition[$id] = $definition;
        } else if (!is_array($definition)) {
            throw new ComponentException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
        $this->_definition[$id] = $definition;
    }

    /**
     * @param $id
     * @return bool
     */
    #[Pure] public function has($id): bool
    {
        return in_array($id, $this->_ids);
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function setComponents(array $data)
    {
        foreach ($data as $key => $val) {
            $this->set($key, $val);
        }
    }


    /**
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name): mixed
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        return parent::__get($name);
    }

    /**
     * @param $id
     * @return bool
     */
    public function remove($id): bool
    {
        $component = $this->_components[$id];
        $className = $component::class;

        unset($component, $this->_components[$id]);
        unset($this->_definition[$id]);
        if (isset($this->_alias[$id])) {
            unset($this->_components[$this->_alias[$id]]);
            unset($this->_definition[$this->_alias[$id]]);
            unset($this->_alias[$id]);
        }

        Snowflake::getDi()->unset($className);

        return $this->has($id);
    }
}
