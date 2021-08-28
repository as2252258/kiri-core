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
	public function __construct(string $service, ?array $args = [], bool $async_reload = true)
	{
	}


    /**
     * @param object $class
     * @param string $method
     * @return bool
     * @throws Exception
     */
    public static function execute(mixed $params, mixed $class, mixed $method = null): bool
    {
        $class = ['class' => $class];
        if (!empty($params->args)) {
            $class = array_merge($class, $params->args);
        }
        if ($params->async_reload !== true) {
            $pro = di(EventProvider::class);
            $pro->on(OnWorkerExit::class, function () use ($params) {
                di(\Kiri\Di\LocalService::class)->remove($params->service);
            },0);
        }
        Kiri::set($params->service, $class);
        return true;
    }

}
