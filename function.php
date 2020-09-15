<?php

defined('APP_PATH') or define('APP_PATH', __DIR__ . '/../../');

use HttpServer\Http\Response;
use Snowflake\Error\Logger;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use HttpServer\Http\Context;
use Snowflake\Core\ArrayAccess;

if (!function_exists('make')) {


	/**
	 * @param $name
	 * @param $default
	 * @return stdClass
	 * @throws
	 */
	function make($name, $default)
	{
		if (Snowflake::has($name)) {
			$class = Snowflake::app()->$name;
		} else if (Snowflake::has($default)) {
			$class = Snowflake::app()->$default;
		} else {
			$class = Snowflake::createObject($default);
			Snowflake::setAlias($name, $default);
		}
		return $class;
	}


}

if (!function_exists('loadByDir')) {


	/**
	 * @param $path
	 */
	function loadByDir($path)
	{
		$path = rtrim($path, '/');
		foreach (glob($path . '/*') as $value) {
			$value = realpath($value);
			if (is_dir($value)) {
				loadByDir($value);
			} else {
				$pos = strpos($value, '.php');
				if ($pos === false || strlen($value) - 4 != $pos) {
					continue;
				}
				var_dump($value);
				require_once "$value";
			}
		}
	}


}


if (!function_exists('exif_imagetype')) {

	/**
	 * @param $name
	 * @return string
	 */
	function exif_imagetype($name)
	{
		return get_file_extension($name);
	}
}


if (!function_exists('logger')) {


	/**
	 * @return Logger
	 * @throws ComponentException
	 */
	function logger()
	{
		return Snowflake::app()->getLogger();
	}
}

if (!function_exists('get_file_extension')) {

	function get_file_extension($filename)
	{
		$mime_types = array(
			'txt'  => 'text/plain',
			'htm'  => 'text/html',
			'html' => 'text/html',
			'php'  => 'text/html',
			'css'  => 'text/css',
			'js'   => 'application/javascript',
			'json' => 'application/json',
			'xml'  => 'application/xml',
			'swf'  => 'application/x-shockwave-flash',
			'flv'  => 'video/x-flv',

			// images
			'png'  => 'image/png',
			'jpeg' => 'image/jpeg',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'ico'  => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'svg'  => 'image/svg+xml',

			// archives
			'zip'  => 'application/zip',
			'rar'  => 'application/x-rar-compressed',
			'exe'  => 'application/x-msdownload',
			'msi'  => 'application/x-msdownload',
			'cab'  => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3'  => 'audio/mpeg',
			'qt'   => 'video/quicktime',
			'mov'  => 'video/quicktime',

			// adobe
			'pdf'  => 'application/pdf',
			'psd'  => 'image/vnd.adobe.photoshop',
			'ai'   => 'application/postscript',
			'eps'  => 'application/postscript',
			'ps'   => 'application/postscript',

			// ms office
			'doc'  => 'application/msword',
			'rtf'  => 'application/rtf',
			'xls'  => 'application/vnd.ms-excel',
			'ppt'  => 'application/vnd.ms-powerpoint',

			// open office
			'odt'  => 'application/vnd.oasis.opendocument.text',
			'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$explode = explode('.', $filename);
		$ext = strtolower(array_pop($explode));
		if (array_key_exists($ext, $mime_types)) {
			return $ext;
		} elseif (function_exists('finfo_open')) {
			$fInfo = finfo_open(FILEINFO_MIME);
			$mimeType = finfo_file($fInfo, $filename);
			finfo_close($fInfo);
			$mimeType = current(explode('; ', $mimeType));
			if (($search = array_search($mimeType, $mime_types)) == false) {
				return $mimeType;
			}
			return $search;
		} else {
			return 'application/octet-stream';
		}
	}
}

if (!function_exists('request')) {

	/**
	 * @return mixed|null
	 */
	function request(): \HttpServer\Http\Request
	{
		if (!Context::hasContext('request')) {
			return make('request', \HttpServer\Http\Request::class);
		}
		return Context::getContext('request');
	}

}

if (!function_exists('Input')) {

	/**
	 * @return mixed|null
	 */
	function Input()
	{
		return request()->params;
	}

}

if (!function_exists('storage')) {

	/**
	 * @param string $fileName
	 * @param string $path
	 * @return string
	 * @throws Exception
	 */
	function storage($fileName = '', $path = '')
	{
		$basePath = Snowflake::getStoragePath();
		if (empty($path)) {
			$fileName = rtrim($basePath, '/') . '/' . $fileName;
		} else if (empty($fileName)) {
			return rtrim(initDir($basePath, $path));
		} else {
			$fileName = rtrim(initDir($basePath, $path)) . '/' . $fileName;
		}
		if (!file_exists($fileName)) {
			touch($fileName);
		}
		return $fileName;
	}


	/**
	 * @param $basePath
	 * @param $path
	 * @return false|string
	 * @throws Exception
	 */
	function initDir($basePath, $path)
	{
		$explode = array_filter(explode('/', $path));
		$_path = '/' . trim($basePath, '/') . '/';
		foreach ($explode as $value) {
			$_path .= $value . '/';
			if (!is_dir(rtrim($_path, '/'))) {
				mkdir(rtrim($_path, '/'));
			}
			if (!is_dir($_path)) {
				throw new Exception('System error, directory ' . $_path . ' is not writable');
			}
		}
		return realpath($_path);
	}


}


if (!function_exists('alias')) {

	/**
	 * @param $class
	 * @param $name
	 */
	function alias($class, $name)
	{
		Snowflake::setAlias($class, $name);
	}

}


if (!function_exists('name')) {

	/**
	 * @param string $name
	 */
	function name($name)
	{
		swoole_set_process_name($name);
	}

}

if (!function_exists('response')) {

	/**
	 * @return Response|stdClass
	 * @throws
	 */
	function response()
	{
		if (!Context::hasContext('response')) {
			return make('response', Response::class);
		}
		return Context::getContext('response');
	}

}

if (!function_exists('send')) {

	/**
	 * @param $context
	 * @param $statusCode
	 * @return bool|Response|stdClass|string
	 * @throws Exception
	 */
	function send($context, $statusCode = 200)
	{
		return \response()->send($context, $statusCode);
	}

}

if (!function_exists('redirect')) {

	function redirect($url)
	{
		return response()->redirect($url);
	}

}


if (!function_exists('env')) {

	/**
	 * @param $key
	 * @param null $default
	 * @return array|false|string|null
	 */
	function env($key, $default = null)
	{
		$env = getenv($key);
		if ($env === false) {
			return $default;
		}
		return $env;
	}

}

if (!function_exists('sweep')) {

	/**
	 * @param string $configPath
	 * @return array|false|string|null
	 */
	function sweep($configPath = APP_PATH . '/config')
	{
		$array = [];
		foreach (glob($configPath . '/*') as $config) {
			$array = array_merge(require_once "$config", $array);
		}
		return $array;
	}

}


if (!function_exists('merge')) {


	/**
	 * @param $param
	 * @param $param1
	 * @return array
	 */
	function merge($param, $param1)
	{
		return ArrayAccess::merge($param, $param1);
	}

}


if (!function_exists('jTraceEx')) {

	/**
	 * @param $e
	 * @param null $seen
	 * @return string
	 */
	function jTraceEx($e, $seen = null)
	{
		$starter = $seen ? 'Caused by: ' : '';
		$result = array();
		if (!$seen) $seen = array();
		$trace = $e->getTrace();
		$prev = $e->getPrevious();
		$result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
		$file = $e->getFile();
		$line = $e->getLine();
		while (true) {
			$current = "$file:$line";
			if (is_array($seen) && in_array($current, $seen)) {
				$result[] = sprintf(' ... %d more', count($trace) + 1);
				break;
			}
			$result[] = sprintf(' at %s%s%s(%s%s%s)',
				count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
				count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
				count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
				$line === null ? $file : basename($file),
				$line === null ? '' : ':',
				$line === null ? '' : $line);
			if (is_array($seen))
				$seen[] = "$file:$line";
			if (!count($trace))
				break;
			$file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
			$line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
			array_shift($trace);
		}
		$result = join("\n", $result);
		if ($prev)
			$result .= "\n" . jTraceEx($prev, $seen);

		return $result;
	}


}
