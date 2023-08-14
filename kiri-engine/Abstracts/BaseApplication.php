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
use Kiri\Events\EventInterface;
use Kiri\Di\LocalService;
use Kiri\Config\ConfigProvider;
use Kiri\Exception\{InitException};
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Kiri\Events\EventProvider;
use ReflectionException;
use Monolog\Logger;
use Kiri\Error\StdoutLogger;

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


    const LOGGER_LEVELS = [Logger::EMERGENCY, Logger::ALERT, Logger::CRITICAL, Logger::ERROR, Logger::WARNING, Logger::NOTICE, Logger::INFO, Logger::DEBUG];

    /**
     * @param ConfigProvider $config
     * @return void
     */
    public function mapping(ConfigProvider $config): void
    {
        $this->container->bind(LoggerInterface::class, new StdoutLogger());
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
        $storage = $config->get('storage', 'storage');
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


    /**
     * @param ConfigProvider $config
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Exception
     */
    public function parseEvents(ConfigProvider $config): void
    {
        $events = $config->get('events', []);
        foreach ($events as $key => $value) {
            if (is_string($value)) {
                $value = $this->container->get($value);
                if (!($value instanceof EventInterface)) {
                    throw new Exception("Event listen must implement " . EventInterface::class);
                }
                $value = [$value, 'process'];
            }
            $this->provider->on($key, $value, 0);
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
