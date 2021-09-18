<?php
declare(strict_types=1);


namespace Http\Handler;


/**
 * Interface AuthIdentity
 * @package Kiri\Kiri\Http
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
