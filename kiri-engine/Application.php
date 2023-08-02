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
use Symfony\Component\Console\{Application as ConsoleApplication,
    Input\ArgvInput,
    Output\ConsoleOutput,
    Output\OutputInterface
};
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
     */
    public function init(): void
    {
        $this->errorHandler->registerShutdownHandler(\config('error.shutdown', []));
        $this->errorHandler->registerExceptionHandler(\config('error.exception', []));
        $this->errorHandler->registerErrorHandler(\config('error.error', []));
        $this->id = \config('id', uniqid('id.'));
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
        $class = Kiri::getDi()->get($service);
        if (method_exists($class, 'onImport')) {
            $class->onImport($this->localService);
        }
        return $this;
    }


    /**
     * @param Kernel $kernel
     * @return $this
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
     * @throws ReflectionException
     */
    public function command(string $command): void
    {
        $container = Kiri::getDi();
        $console = $container->get(ConsoleApplication::class);
        $console->add($container->get($command));
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
        $container = Kiri::getDi();

        [$input, $output] = $this->argument($argv);
        $console = $container->get(ConsoleApplication::class);
        $command = $console->find($input->getFirstArgument());

        if (!($command instanceof Kiri\Server\ServerCommand)) {
            $scanner = $container->get(Scanner::class);
            $scanner->read(APP_PATH . 'app/');
        } else if (\config('reload.hot', false) === false) {
            $scanner = $container->get(Scanner::class);
            $scanner->read(APP_PATH . 'app/');
        } else {
            on(OnWorkerStart::class, function () {
                $scanner = di(Scanner::class);
                $scanner->read(APP_PATH . 'app/');
            });
        }

        fire(new OnBeforeCommandExecute());

        $command->run($input, $output);
        fire(new OnAfterCommandExecute());
        $output->writeln('ok' . PHP_EOL);
    }


    /**
     * @param $argv
     * @return array
     */
    private function argument($argv): array
    {
        $container = Kiri::getDi();
        $input = new ArgvInput($argv);
        $container->bind(ArgvInput::class, $input);

        $output = new ConsoleOutput();
        $container->bind(OutputInterface::class, $output);

        return [$input, $output];
    }
}
