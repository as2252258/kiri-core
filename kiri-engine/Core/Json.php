<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:04
 */

declare(strict_types=1);

namespace Kiri\Core;

use Error;
use Exception;
use Throwable;

/**
 * Class JSON
 * @package Kiri\Core
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
			return false;
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
	public static function decode($data, bool $asArray = true): mixed
	{
		if (is_array($data) || is_numeric($data)) {
			return $data;
		}
		if (!is_string($data)) return null;
		return json_decode($data, $asArray);
	}
	
	
	/**
	 * @param string $message
	 * @param int $code
	 * @param array|string $data
	 * @param int $count
	 * @return string
	 */
	public static function jsonFail(string $message, int $code = 500, array|string $data = [], int $count = 0): string
	{
		return json_encode(['code' => $code, 'param' => $data, 'message' => $message, 'count' => $count], JSON_UNESCAPED_UNICODE);
	}
	
	
	/**
	 * @param string $message
	 * @param array|string $data
	 * @param int $count
	 * @return string
	 */
	public static function jsonSuccess(array|string $data = [], string $message = "ok", int $count = 0): string
	{
		return json_encode(['code' => 0, 'param' => $data, 'message' => $message, 'count' => $count], JSON_UNESCAPED_UNICODE);
	}
	
	
	/**
	 * @param $code
	 * @param string|array $message
	 * @param array|int $data
	 * @param int $count
	 * @param array $exPageInfo
	 * @return string|bool
	 */
	public static function to($code, string|array $message = '', array|int $data = [], int $count = 0, array $exPageInfo = []): string|bool
	{
		$params['code'] = $code;
		if (!is_string($message)) {
			$params['message'] = 'System success.';
			$params['param'] = $message;
			$params['exPageInfo'] = $data;
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
