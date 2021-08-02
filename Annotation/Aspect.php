<?php


namespace Annotation;


use Exception;
use Snowflake\Aop;
use Snowflake\IAspect;
use Snowflake\Snowflake;

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
    public function __construct(public string $aspect)
    {
    }


	/**
	 * @throws Exception
	 */
	public function execute(mixed $class, mixed $method = ''): bool
    {
        return true;
    }


}
