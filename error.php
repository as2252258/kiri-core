<?php

define('SUCCESS', 0);
define('NO_AUTH', 401);

define('ERROR_MESSAGES', [
	SUCCESS => 'ok',
	NO_AUTH => ''
]);

if (!function_exists('message')) {

	/**
	 * @param $code
	 * @param $replace
	 * @param string $default
	 * @return mixed|string
	 */
	function message($code, $replace, $default = '')
	{
		if (!isset(ERROR_MESSAGES[$code])) {
			if (!empty($default)) {
				return $default;
			}
			return 'unknown error';
		}
		return sprintf(ERROR_MESSAGES[$code], $replace);
	}


}
