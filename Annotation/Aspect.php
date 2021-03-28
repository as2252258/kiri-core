<?php


namespace Annotation;


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
     * @param array $handler
     * @return mixed
     */
    public function execute(array $handler): mixed
    {
        if (!in_array(IAspect::class, class_implements($this->aspect))) {
            throw new \Exception(ASPECT_ERROR . IAspect::class);
        }
        /** @var Aop $aop */
        $aop = Snowflake::app()->get('aop');

        $aop->aop_add($handler, $this->aspect);

        return parent::execute($handler); // TODO: Change the autogenerated stub
    }


}
