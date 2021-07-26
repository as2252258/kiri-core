<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 19:09
 */
declare(strict_types=1);

namespace Snowflake\Process;


use Exception;
use Server\SInterface\CustomProcess;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Event;
use Swoole\Timer;

/**
 * Class ServerInotify
 * @package Snowflake\Snowflake\Server
 */
class ServerInotify implements CustomProcess
{
    private mixed $inotify;
    private bool $isReloading = false;
    private bool $isReloadingOut = false;
    private array $watchFiles = [];
    private ?array $dirs = [];
    private int $events;

    private int $int = -1;


    /**
     * @param \Swoole\Process $process
     * @return string
     */
    public function getProcessName(\Swoole\Process $process): string
    {
        return 'file change process';
    }


    /**
     * @param \Swoole\Process $process
     * @throws Exception
     */
    public function onHandler(\Swoole\Process $process): void
    {
        // TODO: Implement before() method.
        set_error_handler([$this, 'onErrorHandler']);
        $this->dirs = Config::get('inotify', [APP_PATH]);
        if (extension_loaded('inotify')) {
            $this->inotify = inotify_init();
            $this->events = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;
            foreach ($this->dirs as $dir) {
                if (!is_dir($dir)) continue;
                $this->watch($dir);
            }
            Event::add($this->inotify, [$this, 'check']);
            Event::wait();
        } else {
            $this->loadDirs();
            $this->tick();
        }
    }


    /**
     * @param bool $isReload
     * @throws Exception
     */
    private function loadDirs(bool $isReload = false)
    {
        foreach ($this->dirs as $value) {
            if (is_bool($path = realpath($value))) {
                continue;
            }

            if (!is_dir($path)) continue;

            $this->loadByDir($path, $isReload);
        }
    }


    private array $md5Map = [];


    /**
     * @throws Exception
     */
    public function tick()
    {
        if ($this->isReloading) {
            return;
        }

        $this->loadDirs(true);

        Timer::after(2000, [$this, 'tick']);
    }


    /**
     * @param $path
     * @param bool $isReload
     * @return void
     * @throws Exception
     */
    private function loadByDir($path, bool $isReload = false): void
    {
        if (!is_string($path)) {
            return;
        }
        $path = rtrim($path, '/');
        foreach (glob(realpath($path) . '/*') as $value) {
            if (is_dir($value)) {
                $this->loadByDir($value, $isReload);
            }
            if (is_file($value)) {
                if ($this->checkFile($value, $isReload)) {
                    $this->timerReload();
                    break;
                }
            }
        }
    }


    /**
     * @param $value
     * @param $isReload
     * @return bool
     */
    private function checkFile($value, $isReload): bool
    {
        $md5 = md5($value);
        $mTime = filectime($value);
        if (!isset($this->md5Map[$md5])) {
            if ($isReload) {
                return true;
            }
            $this->md5Map[$md5] = $mTime;
        } else {
            if ($this->md5Map[$md5] != $mTime) {
                if ($isReload) {
                    return true;
                }
                $this->md5Map[$md5] = $mTime;
            }
        }
        return false;
    }


    const LISTEN_TYPE = [IN_CREATE, IN_DELETE, IN_MODIFY, IN_MOVED_TO, IN_MOVED_FROM];


    /**
     * 开始监听
     */
    public function check()
    {
        if (!($events = inotify_read($this->inotify))) {
            return;
        }
        if ($this->isReloading) {
            if (!$this->isReloadingOut) {
                $this->isReloadingOut = true;
            }
            return;
        }

        foreach ($events as $ev) {
            if (!in_array($ev['mask'], static::LISTEN_TYPE)) {
                continue;
            }
            //非重启类型
            if (str_ends_with($ev['name'], '.php')) {
                if ($this->int !== -1) {
                    return;
                }
                $this->int = @swoole_timer_after(2000, [$this, 'reload']);

                $this->isReloading = true;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function reload()
    {
        $this->isReloading = true;
        $this->trigger_reload();

        $this->clearWatch();
        foreach ($this->dirs as $root) {
            $this->watch($root);
        }
        $this->int = -1;
        $this->isReloading = FALSE;
        $this->isReloadingOut = FALSE;
        $this->md5Map = [];
    }

    /**
     * @throws Exception
     */
    public function timerReload()
    {
        $this->isReloading = true;
        $this->trigger_reload();

        $this->int = -1;

        $this->loadDirs();

        $this->isReloading = FALSE;
        $this->isReloadingOut = FALSE;

        $this->tick();
    }


    /**
     * 重启
     * @throws Exception
     */
    public function trigger_reload()
    {
        exec(PHP_BINARY . ' ' . APP_PATH . 'snowflake runtime:builder', $output);

        print_r(implode(PHP_EOL, $output));

        Snowflake::reload();
    }


    /**
     * @throws Exception
     */
    public function clearWatch()
    {
        foreach ($this->watchFiles as $wd) {
            try {
                inotify_rm_watch($this->inotify, $wd);
            } catch (\Throwable $exception) {
                logger()->addError($exception, 'throwable');
            }
        }
        $this->watchFiles = [];
    }


    /**
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     * @throws Exception
     */
    protected function onErrorHandler($code, $message, $file, $line)
    {
        if (str_contains($message, 'The file descriptor is not an inotify instance')) {
            return;
        }
        debug('Error:' . $message . ' at ' . $file . ':' . $line);
    }


    const IG_DIR = [APP_PATH . 'commands', APP_PATH . '.git', APP_PATH . '.gitee'];


    /**
     * @param $dir
     * @return bool
     * @throws Exception
     */
    public function watch($dir): bool
    {
        //目录不存在
        if (!is_dir($dir)) {
            return logger()->addError("[$dir] is not a directory.");
        }
        //避免重复监听
        if (isset($this->watchFiles[$dir])) {
            return FALSE;
        }

        if (in_array($dir, self::IG_DIR)) {
            return FALSE;
        }

        $wd = @inotify_add_watch($this->inotify, $dir, $this->events);
        $this->watchFiles[$dir] = $wd;

        $files = scandir($dir);
        foreach ($files as $f) {
            if ($f == '.' || $f == '..') {
                continue;
            }
            $path = $dir . '/' . $f;
            //递归目录
            if (is_dir($path)) {
                $this->watch($path);
            } else if (!str_ends_with($f, '.php')) {
                continue;
            }

            //检测文件类型
            if (strstr($f, '.') == '.php') {
                $wd = @inotify_add_watch($this->inotify, $path, $this->events);
                $this->watchFiles[$path] = $wd;
            }
        }
        return TRUE;
    }
}
