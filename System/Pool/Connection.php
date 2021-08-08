<?php
declare(strict_types=1);

namespace Snowflake\Pool;

use Exception;
use HttpServer\Http\Context;
use PDO;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Error;
use Swoole\Runtime;
use Throwable;

/**
 * Class Connection
 * @package Snowflake\Pool
 */
class Connection extends Component
{

    use Alias;


    /**
     * @param $cds
     * @return bool
     *
     * db is in transaction
     * @throws Exception
     */
    public function inTransaction($cds): bool
    {
        $name = $this->name('Mysql:' . $cds, true);
        return Context::getContext('begin_' . $name) == 0;
    }

    /**
     * @param $coroutineName
     * @throws Exception
     */
    public function beginTransaction($coroutineName)
    {
        $coroutineName = $this->name('Mysql:' . $coroutineName, true);
        if (!Context::hasContext('begin_' . $coroutineName)) {
            Context::setContext('begin_' . $coroutineName, 0);
        }
        if (Context::increment('begin_' . $coroutineName) != 0) {
            return;
        }
        $connection = Context::getContext($coroutineName);
        if ($connection instanceof PDO && !$connection->inTransaction()) {
            $connection->beginTransaction();
        }
    }

    /**
     * @param $coroutineName
     * @throws Exception
     */
    public function commit($coroutineName)
    {
        $coroutineName = $this->name('Mysql:' . $coroutineName, true);
        if (Context::decrement('begin_' . $coroutineName) != 0) {
            return;
        }
        $connection = Context::getContext($coroutineName);
        if ($connection instanceof PDO && $connection->inTransaction()) {
            $connection->commit();
        }
    }


    /**
     * @param $coroutineName
     * @throws Exception
     */
    public function rollback($coroutineName)
    {
        $coroutineName = $this->name('Mysql:' . $coroutineName, true);
        if (Context::decrement('begin_' . $coroutineName) != 0) {
            return;
        }
        if (($connection = Context::getContext($coroutineName)) instanceof PDO) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }
    }


    /**
     * @param mixed $config
     * @param bool $isMaster
     * @return mixed
     * @throws Exception
     */
    public function get(mixed $config, bool $isMaster = false): ?PDO
    {
        $coroutineName = $this->name('Mysql:' . $config['cds'], $isMaster);
        if (($pdo = Context::getContext($coroutineName)) instanceof PDO) {
            return $pdo;
        }
        /** @var PDO $connections */
        $connections = $this->getPool()->get($coroutineName, $this->create($coroutineName, $config));
        if ($number = Context::getContext('begin_' . $coroutineName)) {
            $number > 0 && $connections->beginTransaction();
        }
        return Context::setContext($coroutineName, $connections);
    }


    /**
     * @param $coroutineName
     * @param $config
     * @return \Closure
     */
    public function create($coroutineName, $config)
    {
        return static function () use ($coroutineName, $config) {
            if (Coroutine::getCid() === -1) {
                Runtime::enableCoroutine(false);
            }
            $cds = 'mysql:dbname=' . $config['database'] . ';host=' . $config['cds'];
            $link = new PDO($cds, $config['username'], $config['password'], [
                PDO::ATTR_EMULATE_PREPARES         => false,
                PDO::ATTR_CASE                     => PDO::CASE_NATURAL,
                PDO::ATTR_TIMEOUT                  => 60,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES ' . ($config['charset'] ?? 'utf8mb4')
            ]);
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $link->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            $link->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
            return $link;
        };
    }


    /**
     * @param $name
     * @param $isMaster
     * @param $max
     * @throws Exception
     */
    public function initConnections($name, $isMaster, $max)
    {
        $this->getPool()->initConnections($name, $isMaster, $max);
    }


    /**
     * @param $coroutineName
     * @param $isMaster
     * @throws Exception
     */
    public function release($coroutineName, $isMaster)
    {
        $coroutineName = $this->name('Mysql:' . $coroutineName, $isMaster);
        /** @var PDO $client */
        if (!($client = Context::getContext($coroutineName)) instanceof PDO) {
            return;
        }
        if ($client->inTransaction()) {
            return;
        }
        $this->getPool()->push($coroutineName, $client);
        Context::remove($coroutineName);
    }


    /**
     * @param $coroutineName
     * @return bool
     */
    private function hasClient($coroutineName): bool
    {
        return Context::hasContext($coroutineName);
    }


    /**
     * batch release
     * @throws Exception
     */
    public function connection_clear($name, $isMaster)
    {
        $this->getPool()->clean($this->name($name, $isMaster));
    }


    /**
     * @param string $name
     * @param mixed $client
     * @return bool
     * @throws Exception
     */
    public function checkCanUse(string $name, mixed $client): bool
    {
        try {
            if (empty($client) || !($client instanceof PDO)) {
                $result = false;
            } else {
                $result = true;
            }
        } catch (Error | Throwable $exception) {
            $result = $this->addError($exception, 'mysql');
        } finally {
            return $result;
        }
    }


    /**
     * @param $coroutineName
     * @param bool $isMaster
     * @throws Exception
     */
    public function disconnect($coroutineName, bool $isMaster = false)
    {
        Context::remove($coroutineName);
        $coroutineName = $this->name('Mysql:' . $coroutineName, $isMaster);
        $this->getPool()->clean($coroutineName);
    }


    /**
     * @return Pool
     * @throws Exception
     */
    public function getPool(): Pool
    {
        return Snowflake::getDi()->get(Pool::class);
    }

}
