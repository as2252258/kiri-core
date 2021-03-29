<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */
declare(strict_types=1);

namespace Snowflake;


use Annotation\Aspect;
use Console\Console;
use Console\ConsoleProviders;
use Database\DatabasesProviders;
use Database\InjectProperty;
use Exception;
use HttpServer\ServerProviders;
use Snowflake\Abstracts\BaseApplication;
use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Input;
use Snowflake\Abstracts\Kernel;
use Snowflake\Crontab\CrontabProviders;
use Snowflake\Exception\NotFindClassException;
use stdClass;
use Swoole\Timer;

/**
 * Class Init
 *
 * @package Snowflake
 *
 * @property-read Config $config
 */
class Application extends BaseApplication
{

    /**
     * @var string
     */
    public string $id = 'uniqueId';


    public string $state = '';


    /**
     * @throws NotFindClassException
     */
    #[Aspect(InjectProperty::class)] public function init()
    {
        $this->import(ConsoleProviders::class);
        $this->import(DatabasesProviders::class);
        $this->import(ServerProviders::class);

        $this->import(CrontabProviders::class);
    }


    /**
     * @param string $service
     * @return $this
     * @throws
     */
    public function import(string $service): static
    {
        if (!class_exists($service)) {
            throw new NotFindClassException($service);
        }
        $class = Snowflake::createObject($service);
        if (method_exists($class, 'onImport')) {
            $class->onImport($this);
        }
        return $this;
    }


    /**
     * @param $kernel
     * @return $this
     */
    public function commands(Kernel $kernel): static
    {
        foreach ($kernel->getCommands() as $command) {
            $this->register($command);
        }
        return $this;
    }


    /**
     * @param string $command
     * @throws
     */
    public function register(string $command)
    {
        /** @var Console $abstracts */
        $abstracts = $this->get('console');
        $abstracts->register($command);
    }


    /**
     * @param Input $argv
     * @return void
     * @throws Exception
     */
    public function start(Input $argv): void
    {
        try {
            fire(Event::SERVER_BEFORE_START);

            $manager = Snowflake::app()->get('console');
            $manager->setParameters($argv);
            $class = $manager->search();
            response()->send($manager->execCommand($class));
        } catch (\Throwable $exception) {
            response()->send(implode("\n", [
                'Msg: ' . $exception->getMessage(),
                'Line: ' . $exception->getLine(),
                'File: ' . $exception->getFile()
            ]));
        } finally {
            Timer::clearAll();
        }
    }

    /**
     * @param $className
     * @param null $abstracts
     * @return stdClass
     * @throws Exception
     */
    public function make($className, $abstracts = null): stdClass
    {
        return make($className, $abstracts);
    }
}
