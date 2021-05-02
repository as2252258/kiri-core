<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 15:23
 */
declare(strict_types=1);

namespace Database;


use Exception;
use PDO;
use PDOStatement;
use Snowflake\Abstracts\Component;
use Swoole\Coroutine;

/**
 * Class Command
 * @package Database
 */
class Command extends Component
{
    const ROW_COUNT = 'ROW_COUNT';
    const FETCH = 'FETCH';
    const FETCH_ALL = 'FETCH_ALL';
    const EXECUTE = 'EXECUTE';
    const FETCH_COLUMN = 'FETCH_COLUMN';

    const DB_ERROR_MESSAGE = 'The system is busy, please try again later.';

    /** @var Connection */
    public Connection $db;

    /** @var ?string */
    public ?string $sql = '';

    /** @var array */
    public array $params = [];

    /** @var string */
    private string $_modelName;

    private ?PDOStatement $prepare = null;


    /**
     * @return array|bool|int|string|PDOStatement|null
     * @throws Exception
     */
    public function incrOrDecr(): array|bool|int|string|PDOStatement|null
    {
        return $this->execute(static::EXECUTE);
    }

    /**
     * @param bool $isInsert
     * @param bool $hasAutoIncrement
     * @return int|bool|array|string|null
     * @throws Exception
     */
    public function save($isInsert = TRUE, $hasAutoIncrement = null): int|bool|array|string|null
    {
        return $this->execute(static::EXECUTE, $isInsert, $hasAutoIncrement);
    }


    /**
     * @return int|bool|array|string|null
     * @throws Exception
     */
    public function all(): int|bool|array|string|null
    {
        return $this->execute(static::FETCH_ALL);
    }

    /**
     * @return array|bool|int|string|null
     * @throws Exception
     */
    public function one(): null|array|bool|int|string
    {
        return $this->execute(static::FETCH);
    }

    /**
     * @return int|bool|array|string|null
     * @throws Exception
     */
    public function fetchColumn(): int|bool|array|string|null
    {
        return $this->execute(static::FETCH_COLUMN);
    }

    /**
     * @return int|bool|array|string|null
     * @throws Exception
     */
    public function rowCount(): int|bool|array|string|null
    {
        return $this->execute(static::ROW_COUNT);
    }

    /**
     * @return int|bool|array|string|null
     * @throws Exception
     */
    public function flush(): int|bool|array|string|null
    {
        return $this->execute(static::EXECUTE);
    }

    /**
     * @param $type
     * @param null $isInsert
     * @param bool $hasAutoIncrement
     * @return int|bool|array|string|null
     * @throws Exception
     */
    private function execute($type, $isInsert = null, $hasAutoIncrement = null): int|bool|array|string|null
    {
        try {
            if ($type === static::EXECUTE) {
                $result = $this->insert_or_change($isInsert, $hasAutoIncrement);
            } else {
                $result = $this->search($type);
            }
            return $result;
        } catch (\Throwable $exception) {
            return $this->addError($this->sql . '. error: ' . $exception->getMessage(), 'mysql');
        }
    }


    /**
     * @param $type
     * @return mixed
     * @throws Exception
     */
    private function search($type): mixed
    {
        if (!(($connect = $this->db->getConnect($this->sql)) instanceof PDO)) {
            return $this->addError('get client error.', 'mysql');
        }
        if (!(($prepare = $connect->query($this->sql)) instanceof PDOStatement)) {
            $error = $prepare->errorInfo()[2] ?? static::DB_ERROR_MESSAGE;
            return $this->addError($this->sql . ':' . $error, 'mysql');
        }
        if ($type === static::FETCH_COLUMN) {
            $data = $prepare->fetchAll(PDO::FETCH_ASSOC);
        } else if ($type === static::ROW_COUNT) {
            $data = $prepare->rowCount();
        } else if ($type === static::FETCH_ALL) {
            $data = $prepare->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $data = $prepare->fetch(PDO::FETCH_ASSOC);
        }
        $prepare->closeCursor();
        return $data;
    }


    /**
     * @param $isInsert
     * @param $hasAutoIncrement
     * @return bool|string|int
     * @throws Exception
     */
    private function insert_or_change($isInsert, $hasAutoIncrement): bool|string|int
    {
        if (!(($connect = $this->db->getConnect($this->sql)) instanceof PDO)) {
            return $this->addError('get client error.', 'mysql');
        }
        if (!(($prepare = $connect->prepare($this->sql, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL])) instanceof PDOStatement)) {
            $error = $prepare->errorInfo()[2] ?? static::DB_ERROR_MESSAGE;
            return $this->addError($this->sql . ':' . $error, 'mysql');
        }
        if ($prepare->execute() === false) {
            $response = $this->addError(static::DB_ERROR_MESSAGE, 'mysql');
        } else {
            $result = $connect->lastInsertId();
            if ($isInsert === false) {
                $response = true;
            } else if ($result == 0 && $hasAutoIncrement->isAutoIncrement()) {
                $response = $this->addError(static::DB_ERROR_MESSAGE, 'mysql');
            } else {
                $response = $result == 0 ? true : $result;
            }
        }
        $prepare->closeCursor();
        return $response;
    }



    /**
     * @param $modelName
     * @return $this
     */
    public function setModelName($modelName): static
    {
        $this->_modelName = $modelName;
        return $this;
    }

    /**
     * @return string
     */
    public function getModelName(): string
    {
        return $this->_modelName;
    }

    /**
     * @return int|bool|array|string|null
     * @throws Exception
     */
    public function delete(): int|bool|array|string|null
    {
        return $this->execute(static::EXECUTE);
    }

    /**
     * @param null $scope
     * @param bool $insert
     * @return int|bool|array|string|null
     * @throws Exception
     */
    public function exec($scope = null, $insert = false): int|bool|array|string|null
    {
        return $this->execute(static::EXECUTE, $insert, $scope);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function bindValues(array $data = []): static
    {
        if (!is_array($this->params)) {
            $this->params = [];
        }
        if (!empty($data)) {
            $this->params = array_merge($this->params, $data);
        }
        return $this;
    }

    /**
     * @param $sql
     * @return $this
     * @throws Exception
     */
    public function setSql($sql): static
    {
        $this->sql = $sql;
        return $this;
    }

}
