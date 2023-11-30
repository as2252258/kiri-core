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
use Kiri\Config\ConfigProvider;
use Kiri\Exception\{InitException};
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Kiri\Events\EventProvider;
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
     * @param EventProvider $provider
     * @param ConfigProvider $config
     * @param ContainerInterface $container
     * @throws ContainerExceptionInterface
     * @throws InitException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(public EventProvider $provider, public ConfigProvider $config, public ContainerInterface $container)
    {
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
        $this->container->bind(LoggerInterface::class, new StdoutLogger());
        foreach ($config->get('mapping', []) as $interface => $class) {
            $this->container->set($interface, $class);
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
            throw new InitException("Directory $storage does not have write permission");
        }
    }


    /**
     * @param ConfigProvider $config
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
}
