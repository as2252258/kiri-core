<?php


namespace HttpServer;


use Exception;
use Snowflake\Abstracts\Component;


/**
 * Class Shutdown
 * @package HttpServer
 */
class Shutdown extends Component
{


    private string $taskDirectory;
    private string $workerDirectory;
    private string $managerDirectory;
    private string $processDirectory;


    public function init()
    {
        $this->taskDirectory = storage(null, 'pid/task');
        $this->workerDirectory = storage(null, 'pid/worker');
        $this->managerDirectory = storage(null, 'pid/manager');
        $this->processDirectory = storage(null, 'pid/process');
    }


    /**
     * @throws Exception
     */
    public function shutdown(): void
    {
        $master_pid = Server()->setting['pid_file'] ?? PID_PATH;
        clearstatcache($master_pid);
        if (file_exists($master_pid)) {
            $this->close($master_pid);
        }
        $this->closeOther();
    }


    /**
     * 关闭其他进程
     */
    private function closeOther(): void
    {
        $this->directoryCheck($this->managerDirectory);
        $this->directoryCheck($this->taskDirectory);
        $this->directoryCheck($this->workerDirectory);
        $this->directoryCheck($this->processDirectory);
    }


    /**
     * @return bool
     * @throws Exception
     * check server is running.
     */
    public function isRunning()
    {
        $master_pid = Server()->setting['pid_file'] ?? PID_PATH;

        return $this->pidIsExists($master_pid);
    }


    /**
     * @param $content
     * @return bool
     */
    public function pidIsExists($content): bool
    {
        $shell = 'ps -eo pid,cmd,state | grep %d | grep -v grep';
        exec(sprintf($shell, $content), $content, $code);
        if (empty($content)) {
            return false;
        }
        return true;
    }


    /**
     * @param string $path
     */
    public function directoryCheck(string $path)
    {
        $dir = new \DirectoryIterator($path);
        if ($dir->getSize() < 1) {
            return true;
        }
        foreach ($dir as $value) {
            /** @var \DirectoryIterator $value */
            if ($value->isDot()) continue;

            if (!$value->valid()) continue;

            $this->close($value->getRealPath());
        }
        return false;
    }


    /**
     * @param string $value
     */
    public function close(string $value)
    {
        $resource = fopen($value, 'r');
        $content = fgets($resource);
        fclose($resource);

        while ($this->pidIsExists($content)) {
            exec('kill -15 ' . $content);
            sleep(1);
        }

        clearstatcache($value);
        if (file_exists($value)) {
            @unlink($value);
        }
    }


}
