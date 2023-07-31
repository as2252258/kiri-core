<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:13
 */
declare(strict_types=1);

namespace Kiri\Abstracts;


use Database\DatabasesProviders;
use Exception;
use Kiri;
use Kiri\Di\LocalService;
use Kiri\Config\ConfigProvider;
use Kiri\Error\StdoutLogger;
use Kiri\Exception\{InitException};
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Kiri\Events\EventProvider;

/**
 * Class BaseApplication
 * @package Kiri\Base
 * @property DatabasesProviders $connections
 */
abstract class BaseApplication extends Component
{


    /**
     * @var string
     */
    public string $storage = APP_PATH . 'storage';


    /**
     * @var LocalService|mixed
     */
    public LocalService $localService;


    /**
     * @var ContainerInterface
     */
    public ContainerInterface $container;


    /**
     * @var EventProvider
     */
    public EventProvider $provider;

    /**
     * Init constructor.
     *
     *
     * @throws
     */
    public function __construct()
    {
        $this->container = Kiri::getContainer();
        $this->localService = $this->container->get(LocalService::class);

        $this->provider = $this->container->get(EventProvider::class);

        $config = $this->container->get(ConfigProvider::class);

        $this->mapping($config);
        $this->parseStorage($config);
        $this->parseEvents($config);
        parent::__construct();
    }


    /**
     * @param ConfigProvider $config
     * @return void
     */
    public function mapping(ConfigProvider $config): void
    {
        $this->container->set(LoggerInterface::class, StdoutLogger::class);
        foreach ($config->get('mapping', []) as $interface => $class) {
            $this->container->set($interface, $class);
        }

        foreach ($config->get('components', []) as $id => $component) {
            $this->container->set($id, $component);
        }
    }


    /**
     * @param ConfigProvider $config
     * @return void
     * @throws InitException
     */
    public function parseStorage(ConfigProvider $config): void
    {
        if ($storage = $config->get('storage', 'storage')) {
            if (!str_contains($storage, APP_PATH)) {
                $storage = APP_PATH . $storage . '/';
            }
            if (!is_dir($storage)) {
                mkdir($storage, 0777, true);
            }
            if (!is_dir($storage) || !is_writeable($storage)) {
                throw new InitException("Directory {$storage} does not have write permission");
            }
        }
    }


    /**
     * @param ConfigProvider $config
     * @return void
     * @throws Exception
     */
    public function parseEvents(ConfigProvider $config): void
    {
        $events = $config->get('events', []);
        foreach ($events as $key => $value) {
            if (is_string($value)) {
                $value = Kiri::createObject($value);
            }
            $this->addEvent($key, $value);
        }
    }


    /**
     * @param $key
     * @param $value
     * @return void
     * @throws InitException
     * @throws Exception
     */
    private function addEvent($key, $value): void
    {
        if ($value instanceof \Closure || is_object($value)) {
            $this->provider->on($key, $value, 0);
            return;
        }
        if (!is_array($value)) {
            return;
        }
        if (is_object($value[0]) && !($value[0] instanceof \Closure)) {
            $this->provider->on($key, $value, 0);
            return;
        } else if (is_string($value[0])) {
            $value[0] = Kiri::createObject($value[0]);
            $this->provider->on($key, $value, 0);
            return;
        }
        foreach ($value as $item) {
            if (!is_callable($item, true)) {
                throw new InitException("Class does not hav callback.");
            }
            $this->provider->on($key, $item, 0);
        }
    }


    /**
     * @param string $name
     * @return mixed|null
     * @throws Exception
     */
    public function __get(string $name)
    {
        if ($this->localService->has($name)) {
            return $this->localService->get($name);
        }
        return parent::__get($name); // TODO: Change the autogenerated stub
    }


    /**
     * @param $id
     * @param $definition
     */
    public function set($id, $definition): void
    {
        $this->localService->set($id, $definition);
    }


    /**
     * @param $id
     * @return bool
     */
    public function has($id): bool
    {
        return $this->localService->has($id);
    }
}
