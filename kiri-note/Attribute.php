<?php


namespace Note;


/**
 * Class Attribute
 * @package Note
 */
abstract class Attribute implements INote
{


    /**
     * @param static $class
     * @param mixed|string $method
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function execute(mixed $class, mixed $method = ''): mixed
    {
        // TODO: Implement execute() method.
        return true;
    }

}
