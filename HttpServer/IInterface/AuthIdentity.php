<?php
declare(strict_types=1);


namespace HttpServer\IInterface;


/**
 * Interface AuthIdentity
 * @package Snowflake\Snowflake\Http
 */
interface AuthIdentity
{


	public function getIdentity();


	/**
	 * @return string|int
	 * 获取唯一识别码
	 */
	public function getUniqueId(): string|int;

}
