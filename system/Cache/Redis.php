<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 11:00
 */

namespace Snowflake\Cache;

use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Config;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;

/**
 * Class Redis
 * @package Snowflake\Snowflake\Cache
 * @see \Redis
 * @method append($key, $value): int
 * @method auth($password):
 * @method bgSave()
 * @method bgrewriteaof()
 * @method bitcount($key)
 * @method bitop($operation, $ret_key, $key, $other_keys = NULL)
 * @method bitpos($key, $bit, $start = NULL, $end = NULL)
 * @method blPop($key, $timeout_or_key, $extra_args = NULL)
 * @method brPop($key, $timeout_or_key, $extra_args = NULL)
 * @method brpoplpush($src, $dst, $timeout)
 * @method bzPopMax($key, $timeout_or_key, $extra_args = NULL)
 * @method bzPopMin($key, $timeout_or_key, $extra_args = NULL)
 * @method clearLastError()
 * @method client($cmd, $args = NULL)
 * @method close()
 * @method command($args = NULL)
 * @method config($cmd, $key, $value = NULL)
 * @method connect($host, $port = NULL, $timeout = NULL, $retry_interval = NULL)
 * @method dbSize()
 * @method debug($key)
 * @method decr($key)
 * @method decrBy($key, $value)
 * @method delete($key, $other_keys = NULL)
 * @method discard()
 * @method dump($key)
 * @method echo ($msg)
 * @method eval($script, $args = NULL, $num_keys = NULL)
 * @method evalsha($script_sha, $args = NULL, $num_keys = NULL)
 * @method exec()
 * @method exists($key, $other_keys = NULL)
 * @method expireAt($key, $timestamp)
 * @method flushAll($async = NULL)
 * @method flushDB($async = NULL)
 * @method geoadd($key, $lng, $lat, $member, $other_triples = NULL)
 * @method geodist($key, $src, $dst, $unit = NULL)
 * @method geohash($key, $member, $other_members = NULL)
 * @method geopos($key, $member, $other_members = NULL)
 * @method georadius($key, $lng, $lan, $radius, $unit, $opts = [])
 * @method georadius_ro($key, $lng, $lan, $radius, $unit, $opts = [])
 * @method georadiusbymember($key, $member, $radius, $unit, $opts = [])
 * @method georadiusbymember_ro($key, $member, $radius, $unit, $opts = [])
 * @method get($key)
 * @method getAuth()
 * @method getBit($key, $offset)
 * @method getDBNum()
 * @method getHost()
 * @method getKeys($pattern)
 * @method getLastError()
 * @method getMode()
 * @method getMultiple($keys)
 * @method getOption($option)
 * @method getPersistentID()
 * @method getPort()
 * @method getRange($key, $start, $end)
 * @method getReadTimeout()
 * @method getSet($key, $value)
 * @method getTimeout()
 * @method hDel($key, $member, $other_members = NULL)
 * @method hExists($key, $member)
 * @method hGet($key, $member)
 * @method hGetAll($key)
 * @method hIncrBy($key, $member, $value)
 * @method hIncrByFloat($key, $member, $value)
 * @method hKeys($key)
 * @method hLen($key)
 * @method hMget($key, $keys)
 * @method hMset($key, $pairs)
 * @method hSet($key, $member, $value)
 * @method hSetNx($key, $member, $value)
 * @method hStrLen($key, $member)
 * @method hVals($key)
 * @method hscan($str_key, $i_iterator, $str_pattern = NULL, $i_count = NULL)
 * @method incr($key)
 * @method incrBy($key, $value)
 * @method incrByFloat($key, $value)
 * @method info($option = NULL)
 * @method isConnected()
 * @method lGet($key, $index)
 * @method lGetRange($key, $start, $end)
 * @method lInsert($key, $position, $pivot, $value)
 * @method lPop($key)
 * @method lPush($key, $value)
 * @method lPushx($key, $value)
 * @method lRemove($key, $value, $count)
 * @method lSet($key, $index, $value)
 * @method lSize($key)
 * @method lastSave()
 * @method listTrim($key, $start, $stop)
 * @method migrate($host, $port, $key, $db, $timeout, $copy = NULL, $replace = NULL)
 * @method move($key, $dbindex)
 * @method mset($pairs)
 * @method msetnx($pairs)
 * @method multi($mode = NULL)
 * @method object($field, $key)
 * @method pconnect($host, $port = NULL, $timeout = NULL)
 * @method persist($key)
 * @method pexpire($key, $timestamp)
 * @method pexpireAt($key, $timestamp)
 * @method pfadd($key, $elements)
 * @method pfcount($key)
 * @method pfmerge($dstkey, $keys)
 * @method ping()
 * @method pipeline()
 * @method psetex($key, $expire, $value)
 * @method psubscribe($patterns, $callback)
 * @method pttl($key)
 * @method publish($channel, $message)
 * @method pubsub($cmd, $args = NULL)
 * @method punsubscribe($pattern, $other_patterns = NULL)
 * @method rPop($key)
 * @method rPush($key, $value)
 * @method rPushx($key, $value)
 * @method randomKey()
 * @method rawcommand($cmd, $args = NULL)
 * @method renameKey($key, $newkey)
 * @method renameNx($key, $newkey)
 * @method restore($ttl, $key, $value)
 * @method role()
 * @method rpoplpush($src, $dst)
 * @method sAdd($key, $value)
 * @method sAddArray($key, $options)
 * @method sContains($key, $value)
 * @method sDiff($key, $other_keys = NULL)
 * @method sDiffStore($dst, $key, $other_keys = NULL)
 * @method sInter($key, $other_keys = NULL)
 * @method sInterStore($dst, $key, $other_keys = NULL)
 * @method sMembers($key)
 * @method sMove($src, $dst, $value)
 * @method sPop($key)
 * @method sRandMember($key, $count = NULL)
 * @method sRemove($key, $member, $other_members = NULL)
 * @method sSize($key)
 * @method sUnion($key, $other_keys = NULL)
 * @method sUnionStore($dst, $key, $other_keys = NULL)
 * @method save()
 * @method scan($i_iterator, $str_pattern = NULL, $i_count = NULL)
 * @method script($cmd, $args = NULL)
 * @method select($dbindex)
 * @method set($key, $value, $opts = NULL)
 * @method setBit($key, $offset, $value)
 * @method setOption($option, $value)
 * @method setRange($key, $offset, $value)
 * @method setTimeout($key, $timeout)
 * @method setex($key, $expire, $value)
 * @method setnx($key, $value)
 * @method slaveof($host = NULL, $port = NULL)
 * @method slowlog($arg, $option = NULL)
 * @method sort($key, $options = [])
 * @method sortAsc($key, $pattern = NULL, $get = NULL, $start = NULL, $end = NULL, $getList = NULL)
 * @method sortAscAlpha($key, $pattern = NULL, $get = NULL, $start = NULL, $end = NULL, $getList = NULL)
 * @method sortDesc($key, $pattern = NULL, $get = NULL, $start = NULL, $end = NULL, $getList = NULL)
 * @method sortDescAlpha($key, $pattern = NULL, $get = NULL, $start = NULL, $end = NULL, $getList = NULL)
 * @method sscan($str_key, $i_iterator, $str_pattern = NULL, $i_count = NULL)
 * @method strlen($key)
 * @method subscribe($channels, $callback)
 * @method swapdb($srcdb, $dstdb)
 * @method time()
 * @method ttl($key)
 * @method type($key)
 * @method unlink($key, $other_keys = NULL)
 * @method unsubscribe($channel, $other_channels = NULL)
 * @method unwatch()
 * @method wait($numslaves, $timeout)
 * @method watch($key, $other_keys = NULL)
 * @method xack($str_key, $str_group, $arr_ids)
 * @method xadd($str_key, $str_id, $arr_fields, $i_maxlen = NULL, $boo_approximate = NULL)
 * @method xclaim($str_key, $str_group, $str_consumer, $i_min_idle, $arr_ids, $arr_opts = [])
 * @method xdel($str_key, $arr_ids)
 * @method xgroup($str_operation, $str_key = NULL, $str_arg1 = NULL, $str_arg2 = NULL, $str_arg3 = NULL)
 * @method xinfo($str_cmd, $str_key = NULL, $str_group = NULL)
 * @method xlen($key)
 * @method xpending($str_key, $str_group, $str_start = NULL, $str_end = NULL, $i_count = NULL, $str_consumer = NULL)
 * @method xrange($str_key, $str_start, $str_end, $i_count = NULL)
 * @method xread($arr_streams, $i_count = NULL, $i_block = NULL)
 * @method xreadgroup($str_group, $str_consumer, $arr_streams, $i_count = NULL, $i_block = NULL)
 * @method xrevrange($str_key, $str_start, $str_end, $i_count = NULL)
 * @method xtrim($str_key, $i_maxlen, $boo_approximate = NULL)
 * @method zAdd($key, $score, $value)
 * @method zCard($key)
 * @method zCount($key, $min, $max)
 * @method zDelete($key, $member, $other_members = NULL)
 * @method zDeleteRangeByRank($key, $start, $end)
 * @method zDeleteRangeByScore($key, $min, $max)
 * @method zIncrBy($key, $value, $member)
 * @method zInter($key, $keys, $weights = [], $aggregate = NULL)
 * @method zLexCount($key, $min, $max)
 * @method zRange($key, $start, $end, $scores = NULL)
 * @method zRangeByLex($key, $min, $max, $offset = NULL, $limit = NULL)
 * @method zRangeByScore($key, $start, $end, $options = [])
 * @method zRank($key, $member)
 * @method zRemRangeByLex($key, $min, $max)
 * @method zRevRange($key, $start, $end, $scores = NULL)
 * @method zRevRangeByLex($key, $min, $max, $offset = NULL, $limit = NULL)
 * @method zRevRangeByScore($key, $start, $end, $options = [])
 * @method zRevRank($key, $member)
 * @method zScore($key, $member)
 * @method zUnion($key, $keys, $weights = [], $aggregate = NULL)
 * @method zscan($str_key, $i_iterator, $str_pattern = NULL, $i_count = NULL)
 * @method zPopMax($key)
 * @method zPopMin($key)
 * @method del($key, $other_keys = NULL)
 * @method evaluate($script, $args = NULL, $num_keys = NULL)
 * @method evaluateSha($script_sha, $args = NULL, $num_keys = NULL)
 * @method expire($key, $timeout)
 * @method keys($pattern)
 * @method lLen($key)
 * @method lindex($key, $index)
 * @method lrange($key, $start, $end)
 * @method lrem($key, $value, $count)
 * @method ltrim($key, $start, $stop)
 * @method mget($keys)
 * @method open($host, $port = NULL, $timeout = NULL, $retry_interval = NULL)
 * @method popen($host, $port = NULL, $timeout = NULL)
 * @method rename($key, $newkey)
 * @method sGetMembers($key)
 * @method scard($key)
 * @method sendEcho($msg)
 * @method sismember($key, $value)
 * @method srem($key, $member, $other_members = NULL)
 * @method substr($key, $start, $end)
 * @method zRem($key, $member, $other_members = NULL)
 * @method zRemRangeByRank($key, $min, $max)
 * @method zRemRangeByScore($key, $min, $max)
 * @method zRemove($key, $member, $other_members = NULL)
 * @method zRemoveRangeByScore($key, $min, $max)
 * @method zReverseRange($key, $start, $end, $scores = NULL)
 * @method zSize($key)
 * @method zinterstore($key, $keys, $weights = [], $aggregate = NULL)
 * @method zunionstore($key, $keys, $weights = [], $aggregate = NULL)
 */
class Redis extends Component
{
	public $host = '127.0.0.1';
	public $auth = 'xl.2005113426';
	public $port = 6973;
	public $databases = 0;
	public $timeout = -1;
	public $prefix = 'idd';

	/**
	 * @throws Exception
	 */
	public function init()
	{
		$event = Snowflake::app()->event;
		$event->on(Event::RELEASE_ALL, [$this, 'destroy']);
		$event->on(Event::EVENT_AFTER_REQUEST, [$this, 'release']);
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws
	 */
	public function __call($name, $arguments)
	{
		try {
			if (method_exists($this, $name)) {
				$data = $this->{$name}(...$arguments);
			} else {
				$data = $this->proxy()->{$name}(...$arguments);
			}
		} catch (\Throwable $exception) {
			$this->error(print_r($exception, true));
			$data = false;
		} finally {
			return $data;
		}
	}

	/**
	 * 释放连接池
	 * @throws ConfigException
	 */
	public function release()
	{
		$connections = Snowflake::app()->pool->redis;
		$connections->release($this->get_config(), true);
	}

	/**
	 * 销毁连接池
	 * @throws ConfigException
	 */
	public function destroy()
	{
		$connections = Snowflake::app()->pool->redis;
		$connections->destroy($this->get_config(), true);
	}

	/**
	 * @return \Redis
	 * @throws Exception
	 */
	public function proxy()
	{
		$connections = Snowflake::app()->pool->redis;

		$config = $this->get_config();
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];

		$connections->initConnections('redis:' . $name, true);
		$connections->setLength(env('REDIS.POOL_LENGTH', 100));

		$client = $connections->getConnection($config, true);
		if (!($client instanceof \Redis)) {
			throw new Exception('Redis connections more.');
		}
		return $client;
	}

	/**
	 * @return array
	 * @throws ConfigException
	 */
	public function get_config(): array
	{
		$config = Config::get('cache.redis', false, [
			'host'         => '127.0.0.1',
			'port'         => '6379',
			'prefix'       => Config::get('id'),
			'auth'         => '',
			'databases'    => '0',
			'read_timeout' => -1,
			'conn_timeout' => -1,
		]);
		return [
			'host'         => $config['host'],
			'port'         => $config['port'],
			'auth'         => $config['auth'],
			'timeout'      => $config['conn_timeout'],
			'databases'    => $config['databases'],
			'read_timeout' => $config['read_timeout'],
			'prefix'       => $config['prefix'],
		];
	}

}
