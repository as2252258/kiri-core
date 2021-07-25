<?php


namespace Server;


use Swoole\Coroutine\Channel;

class ApplicationStore
{


    private static ?ApplicationStore $applicationStore = null;


    private Channel $lock;


    private function __construct()
    {
        $this->lock = new Channel(99999);
    }


    /**
     * @return \Server\ApplicationStore|null
     */
    public static function getStore()
    {
        if (!(static::$applicationStore instanceof ApplicationStore)) {
            static::$applicationStore = new ApplicationStore();
        }
        return static::$applicationStore;
    }


    public function add()
    {
        $this->lock->push(1);
        return $this;
    }


    public function waite()
    {
        $this->lock->pop(-1);
    }


    public function done()
    {
        $this->lock->pop();
    }


    /**
     * @return bool
     */
    public function isBusy()
    {
        return !$this->lock->isEmpty();
    }


    /**
     * @return string
     */
    public function getStatus(): string
    {
        return env('state');
    }

}
