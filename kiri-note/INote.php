<?php


namespace Note;



interface INote
{

    /**
     * @param mixed $class
     * @param mixed $method
     * @return mixed
     */
    public function execute(mixed $class, mixed $method = ''): mixed;


}
