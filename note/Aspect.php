<?php


namespace Annotation;


use Exception;
use Kiri\AspectManager;
use Kiri\IAspect;
use Kiri\Kiri;

defined('ASPECT_ERROR') or define('ASPECT_ERROR', 'Aspect annotation must implement ');

/**
 * Class Aspect
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Aspect extends Attribute
{


    /**
     * Aspect constructor.
     * @param string $aspect
     */
    public function __construct(string $aspect)
    {
    }


	/**
	 * @throws Exception
	 */
    public static function execute(mixed $params, mixed $class, mixed $method = ''): bool
    {
        return true;
    }


}
