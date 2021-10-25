<?php

namespace Kiri\Proxy;

use Http\Handler\Handler;

interface ProxyInterface
{


    /**
     * @param Handler $executor
     * @return mixed
     */
    public function proxy(Handler $executor): mixed;

}
