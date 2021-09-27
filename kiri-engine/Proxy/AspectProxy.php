<?php

namespace Kiri\Proxy;

use Annotation\Aspect;
use Http\Handler\Handler;
use Kiri\Di\NoteManager;
use Kiri\IAspect;
use Kiri\Kiri;


/**
 *
 */
class AspectProxy extends AProxy implements ProxyInterface
{


    /**
     * @param Handler $executor
     * @return mixed
     */
    public function proxy(Handler $executor): mixed
    {
        if ($executor->callback instanceof \Closure) {
            return call_user_func($executor->callback, ...$executor->params);
        }
        $controller = Kiri::getDi()->get($executor->callback[0]);
        $aspect = $this->getAspect($executor->callback);
        if (!is_null($aspect)) {
            $aspect->before();
            $result = $aspect->invoke([$controller, $executor->callback[1]], $executor->params);
            $aspect->after($result);
        } else {
            $result = call_user_func([$controller, $executor->callback[1]]);
        }
        return $result;
    }


    /**
     * @param array $executor
     * @return ?IAspect
     */
    protected function getAspect(array $executor): ?IAspect
    {
        $aspect = NoteManager::getSpecify_annotation(Aspect::class, $executor[0], $executor[1]);
        if (!is_null($aspect)) {
            $aspect = Kiri::getDi()->get($aspect->aspect);
        }
        return $aspect;
    }
}
