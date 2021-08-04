<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */
declare(strict_types=1);

namespace Snowflake;


use Closure;
use Console\Console;
use Console\ConsoleProviders;
use Database\DatabasesProviders;
use Exception;
use HttpServer\Command;
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
    public function init()
    {
        $this->import(ConsoleProviders::class);
        $this->import(DatabasesProviders::class);
        $this->import(ServerProviders::class);

        $this->import(CrontabProviders::class);
    }





    /**
     * @param Closure|array $closure
     * @return $this
     * @throws Exception
     */
    public function middleware(Closure|array $closure): static
    {
        $this->getRouter()->setMiddleware($closure);
        return $this;
    }


    /**
     * @param bool $useTree
     * @return $this
     * @throws Exception
     */
    public function setUseTree(bool $useTree): static
    {
        $this->getRouter()->setUseTree($useTree);
        return $this;
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
        $class = Snowflake::getDi()->get($service);
        if (method_exists($class, 'onImport')) {
            $class->onImport($this);
        }
        return $this;
    }


	/**
	 * @param Kernel $kernel
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
            /** @var Console $manager */
            $manager = Snowflake::app()->get('console');
            $manager->register(Runtime::class);
            $manager->setParameters($argv);
            $class = $manager->search();
            if (!($class instanceof Command)) {
                scan_directory(directory('app'), 'App');
            }
            $data = response()->getBuilder($manager->execCommand($class));
        } catch (\Throwable $exception) {
            $data = logger()->exception($exception);
        } finally {
            print_r($data);
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
