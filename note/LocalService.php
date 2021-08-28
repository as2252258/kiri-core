<?php


namespace Annotation;


use Exception;
use Kiri\Events\EventProvider;
use Kiri\Kiri;
use Server\Events\OnWorkerExit;

/**
 * Class LocalService
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class LocalService extends Attribute
{


    /**
     * LocalService constructor.
     * @param string $service
     * @param array|null $args
     * @param bool $async_reload
     * @throws Exception
     */
    public function __construct(public string $service, public ?array $args = [], public bool $async_reload = true)
    {
        if ($this->async_reload !== true) {
            $pro = di(EventProvider::class);
            $pro->on(OnWorkerExit::class, function () {
                di(\Kiri\Di\LocalService::class)->remove($this->service);
            }, 0);
        }
    }


    /**
     * @param object $class
     * @param string $method
     * @return bool
     * @throws Exception
     */
    public function execute(mixed $class, mixed $method = null): bool
    {
        $class = ['class' => $class];
        if (!empty($this->args)) {
            $class = array_merge($class, $this->args);
        }
        Kiri::set($this->service, $class);
        return true;
    }

}
