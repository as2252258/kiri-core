<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:29
 */

namespace Http\Handler\Formatter;


/**
 * Interface IFormatter
 * @package Kiri\Kiri\Http\Formatter
 */
interface IFormatter
{

	/**
	 * @param $context
	 * @return static
	 */
	public function send($context): static;


	/**
	 * @return mixed
	 */
	public function getData(): mixed;

	public function clear(): void;
}
