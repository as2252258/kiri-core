<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:28
 */
declare(strict_types=1);

namespace Kiri\Abstracts;


use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri;
use Kiri\Error\StdoutLogger;
use ReflectionException;

/**
 * Class Component
 * @package Kiri\Base
 */
class Component implements Configure
{


    /**
     * BaseAbstract constructor.
     */
    public function __construct()
    {
    }


    /**
     * @return void
     */
    public function init(): void
    {
    }


    /**
     * @return string
     */
    #[Pure] public static function className(): string
    {
        return static::class;
    }


    /**
     * @return StdoutLogger
     * @throws
     */
    public function getLogger(): StdoutLogger
    {
        return Kiri::getLogger();
    }


    /**
     * @param string $name
     * @return mixed
     * @throws
     */
    public function __get(string $name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        } else if (method_exists($this, $name)) {
            return $this->{$name};
        } else {
            throw new Exception('Unable getting property ' . get_called_class() . '::' . $name);
        }
    }


    /**
     * @param string $name
     * @param $value
     * @return void
     * @throws
     */
    public function __set(string $name, $value): void
    {
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } else if (method_exists($this, $name)) {
            $this->{$name} = $value;
        } else {
            throw new Exception('Unable setting property ' . get_called_class() . '::' . $name);
        }
    }


}
