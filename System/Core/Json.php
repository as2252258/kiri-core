<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:04
 */

declare(strict_types=1);

namespace Snowflake\Core;

use Error;
use Exception;
use Throwable;

/**
 * Class JSON
 * @package Snowflake\Snowflake\Core
 */
class Json
{


	/**
	 * @param $data
	 * @return false|string
	 */
	public static function encode($data): bool|string
	{
		if (empty($data)) {
			return $data;
		}
		if (is_array($data)) {
			return json_encode($data, JSON_UNESCAPED_UNICODE);
		}
		return $data;
	}


	/**
	 * @param $data
	 * @param bool $asArray
	 * @return mixed
	 */
	public static function decode($data, $asArray = true): mixed
	{
		if (is_array($data)) {
			return $data;
		}
		return json_decode($data, $asArray);
	}


	/**
	 * @param $code
	 * @param string $message
	 * @param array $data
	 * @param int $count
	 * @param array $exPageInfo
	 * @return mixed
	 * @throws
	 */
	public static function to($code, $message = '', $data = [], $count = 0, $exPageInfo = []): mixed
	{
		$params['code'] = $code;
		if (!is_string($message)) {
			$params['param'] = $message;
			if (!empty($data)) {
				$params['exPageInfo'] = $data;
			}
			$params['message'] = 'System success.';
		} else {
			$params['message'] = $message;
			$params['param'] = $data;
		}
		if (!empty($exPageInfo)) {
			$params['exPageInfo'] = $exPageInfo;
		}
		$params['count'] = $count;
		if (is_numeric($data) || !is_numeric($count)) {
			$params['count'] = $data;
			$params['exPageInfo'] = $count;
		}
		if ((int)$params['count'] == -100) {
			$params['count'] = 1;
		}
		return static::encode($params);
	}


	/**
	 * @param Throwable|Error $throwable
	 * @return bool|string
	 * @throws Exception
	 */
	public static function error(Throwable|Error $throwable): bool|string
	{
		$array['code'] = $throwable->getCode() == 0 ? 500 : $throwable->getCode();
		$array['message'] = $throwable->getMessage();
		$array['param'] = [
			'file' => $throwable->getFile(),
			'line' => $throwable->getLine()
		];
		return Json::encode($array);
	}


	/**
	 * @param $state
	 * @param $body
	 * @return false|int|string
	 * @throws Exception
	 */
	public static function output($state, $body): bool|int|string
	{
		$params['state'] = $state;
		$params['body'] = ArrayAccess::toArray($body);

		return static::encode($params);
	}
}
