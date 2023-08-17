<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */
declare(strict_types=1);

namespace Kiri;


use Exception;
use Kiri;
use Kiri\Abstracts\{BaseApplication, Kernel};
use Kiri\Di\Scanner;
use Kiri\Error\ErrorHandler;
use Kiri\Events\{OnAfterCommandExecute, OnBeforeCommandExecute};
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\{Application as ConsoleApplication, Input\ArgvInput, Output\ConsoleOutput, Output\OutputInterface};
use Kiri\Server\Events\OnWorkerStart;

/**
 * Class Init
 *
 * @package Kiri
 */
class Application extends BaseApplication
{

    /**
     * @var string
     */
    public string $id = 'uniqueId';


    public string $state = '';


    /**
     * @param ErrorHandler $errorHandler
     */
    public function __construct(public ErrorHandler $errorHandler)
    {
        parent::__construct();
    }


    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Exception
     */
    public function init(): void
    {
        $this->errorHandler->registerShutdownHandler(\config('error.shutdown', []));
        $this->errorHandler->registerExceptionHandler(\config('error.exception', []));
        $this->errorHandler->registerErrorHandler(\config('error.error', []));
        $this->id = \config('id', uniqid('id.'));

        $event = $this->container->get(Kiri\Events\EventProvider::class);
        $event->on(OnBeforeCommandExecute::class, [$this, 'beforeCommandExecute']);
    }


    /**
     * @param OnBeforeCommandExecute $beforeCommandExecute
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function beforeCommandExecute(OnBeforeCommandExecute $beforeCommandExecute): void
    {
        if (!($beforeCommandExecute->command instanceof Kiri\Server\ServerCommand)) {
            $scanner = $this->container->get(Scanner::class);
            $scanner->read(APP_PATH . 'app/');
        } else if (\config('reload.hot', false) === false) {
            $scanner = $this->container->get(Scanner::class);
            $scanner->read(APP_PATH . 'app/');
        }
    }


    /**
     * @param string $service
     * @return $this
     * @throws
     */
    public function import(string $service): static
    {
        if (!class_exists($service)) {
            return $this;
        }
        $class = $this->container->get($service);
        if (method_exists($class, 'onImport')) {
            $class->onImport($this->localService);
        }
        return $this;
    }


    /**
     * @param Kernel $kernel
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function commands(Kernel $kernel): static
    {
        foreach ($kernel->getCommands() as $command) {
            $this->command($command);
        }
        return $this;
    }


    /**
     * @param string $command
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function command(string $command): void
    {
        $console = $this->container->get(ConsoleApplication::class);
        $console->add($this->container->get($command));
    }


    /**
     * @param array $argv
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Exception
     */
    public function execute(array $argv): void
    {
        $input = new ArgvInput($argv);
        $this->container->bind(ArgvInput::class, $input);

        $output = new ConsoleOutput();
        $this->container->bind(OutputInterface::class, $output);

        $console = $this->container->get(ConsoleApplication::class);
        $command = $console->find($input->getFirstArgument());

        fire(new OnBeforeCommandExecute($command));

        $command->run($input, $output);
        fire(new OnAfterCommandExecute($command));
        $output->writeln('execute complete.' . PHP_EOL);
    }
    
}
