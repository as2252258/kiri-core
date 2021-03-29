<?php

defined('APP_PATH') or define('APP_PATH', realpath(__DIR__ . '/../../'));


use Annotation\Annotation;
use Annotation\Attribute;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\Route\Router;
use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Config;
use Snowflake\Error\Logger;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use HttpServer\Http\Context;
use Snowflake\Core\ArrayAccess;

if (!function_exists('make')) {


	/**
	 * @param $name
	 * @param $default
	 * @return mixed
	 * @throws
	 */
	function make($name, $default): mixed
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

if (!function_exists('workerName')) {

	function workerName($worker_id)
	{
		return $worker_id >= Snowflake::app()->getSwoole()->setting['worker_num'] ? 'Task' : 'Worker';
	}

}


if (!function_exists('annotation')) {


	/**
	 * @return Annotation
	 * @throws Exception
	 */
	function annotation(): Annotation
	{
		return Snowflake::app()->getAttributes();
	}


}


if (!function_exists('recursive_directory')) {


	/**
	 * @param DirectoryIterator $file
	 * @throws Exception
	 */
	function recursive_callback(DirectoryIterator $file)
	{
		$attributes = Snowflake::app()->getAttributes();

		$annotations = $attributes->getFilename($file->getRealPath());
		if (empty($annotations)) {
			return;
		}

		/** @var Attribute $value */
		foreach ($annotations['methods'] as $name => $attribute) {
			foreach ($attribute as $value) {
				if (!($value instanceof Attribute)) {
					continue;
				}
				$value->execute([$annotations['handler'], $name]);
			}
		}
	}

	/**
	 * @param string $path
	 */
	function recursive_directory(string $path)
	{
		$directoryIterators = new \DirectoryIterator($path);
		foreach ($directoryIterators as $directoryIterator) {
			if ($directoryIterator->getFilename() === '.' || $directoryIterator->getFilename() === '..') {
				continue;
			}
			if ($directoryIterator->isDir()) {
				Recursive_directory($directoryIterator->getRealPath());
			} else {
				call_user_func('recursive_callback', $directoryIterator);
			}
		}
		unset($directoryIterators);
	}


}


if (!function_exists('directory')) {

	/**
	 * @param $name
	 * @return string
	 */
	#[Pure] function directory($name): string
	{
		return realpath(APP_PATH . $name);
	}


}


if (!function_exists('isUrl')) {


	/**
	 * @param $url
	 * @param bool $get_info
	 * @return false|array
	 */
	function isUrl($url, $get_info = true): bool|array
	{
		$queryMatch = '/((http[s]?):\/\/)?(([\w\-\_]+\.)+\w+(:\d+)?)(\/.*)?/';
		if (!preg_match($queryMatch, $url, $outPut)) {
			return false;
		}
		$port = str_replace(':', '', $outPut[5]);

		[$isHttps, $domain, $port, $path] = [$outPut[2] == 'https', $outPut[3], $port, $outPut[6] ?? ''];
		if ($isHttps && empty($port)) {
			$port = 443;
		}

		unset($outPut);

		return [$isHttps == 'https', $domain, $port, $path];
	}

}


if (!function_exists('split_request_uri')) {


	/**
	 * @param $url
	 * @return false|array
	 */
	function split_request_uri($url): bool|array
	{
		if (($parse = isUrl($url, null)) === false) {
			return false;
		}

		[$isHttps, $domain, $port, $path] = $parse;
		$uri = $isHttps ? 'https://' . $domain : 'http://' . $domain;
		if (!empty($port)) {
			$uri .= ':' . $port;
		}
		return [$uri, $path];
	}

}


if (!function_exists('hadDomain')) {


	/**
	 * @param $url
	 * @return false|array
	 */
	function hadDomain($url): bool|array
	{
		$param = split_request_uri($url);
		return !is_array($param) ? false : $param[0];
	}

}


if (!function_exists('isDomain')) {


	/**
	 * @param $url
	 * @return false|array
	 */
	function isDomain($url): array|bool
	{
		return !isIp($url);
	}

}
if (!function_exists('isIp')) {


	/**
	 * @param $url
	 * @return false|array
	 */
	function isIp($url): bool|array
	{
		return preg_match('/(\d{1,3}\.){3}\.\d{1,3}(:\d{1,5})?/', $url);
	}

}


if (!function_exists('loadByDir')) {


	/**
	 * @param $namespace
	 * @param $dirname
	 */
	function classAutoload($namespace, $dirname)
	{
		foreach (glob(rtrim($dirname, '/') . '/*') as $value) {
			$value = realpath($value);
			if (is_dir($value)) {
				classAutoload($namespace, $value);
			} else {
				$pos = strpos($value, '.php');
				if ($pos === false || strlen($value) - 4 != $pos) {
					continue;
				}

				$replace = ltrim(str_replace(__DIR__, '', $value), '/');
				$replace = str_replace('.php', '', $replace);

				$first = explode(DIRECTORY_SEPARATOR, $replace);
				array_shift($first);

				Snowflake::setAutoload($namespace . '\\' . implode('\\', $first), $value);
			}
		}
	}


}


if (!function_exists('write')) {


	/**
	 * @param string $messages
	 * @param string $category
	 * @throws Exception
	 */
	function write(string $messages, $category = 'app')
	{
		$logger = Snowflake::app()->getLogger();
		$logger->write($messages, $category);
	}
}

if (!function_exists('fire')) {


	/**
	 * @param string $event
	 * @param array $params
	 * @throws Exception
	 * @throws Exception
	 */
	function fire(string $event, array $params = [])
	{
		$logger = Snowflake::app()->getEvent();
		$logger->trigger($event, $params);
	}
}

if (!function_exists('aop')) {


	/**
	 * @param string $event
	 * @param array $params
	 * @throws Exception
	 * @throws Exception
	 */
	function aop(mixed $handler, array $params = [])
	{
		return Snowflake::app()->get('aop')->dispatch($handler, ...$params);
	}
}


if (!function_exists('objectPool')) {

	/**
	 * @param string $className
	 * @param callable $construct
	 * @return mixed
	 * @throws Exception
	 */
	function objectPool(mixed $className, callable $construct): mixed
	{
		return Snowflake::app()->getObject()->load($className, $construct);
	}
}

if (!function_exists('objectRecover')) {

	/**
	 * @param string $className
	 * @param $object
	 * @return mixed
	 * @throws Exception
	 */
	function objectRecover(mixed $className, $object): void
	{
		Snowflake::app()->getObject()->release($className, $object);
	}
}


if (!function_exists('instance_load')) {

	function instance_load()
	{
		$content = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
		if (isset($content['autoload']) && isset($content['autoload']['psr-4'])) {
			$psr4 = $content['autoload']['psr-4'];
			foreach ($psr4 as $namespace => $dirname) {
				classAutoload($namespace, __DIR__ . '/' . $dirname);
			}
		}
	}

}


if (!function_exists('exif_imagetype')) {

	/**
	 * @param $name
	 * @return string
	 */
	function exif_imagetype($name): string
	{
		return get_file_extension($name);
	}
}


if (!function_exists('logger')) {


	/**
	 * @return Logger
	 * @throws Exception
	 */
	function logger(): Logger
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
	 * @return Request
	 */
	function request(): Request
	{
		if (!Context::hasContext('request')) {
			return make('request', Request::class);
		}
		return Context::getContext('request');
	}

}

if (!function_exists('Input')) {

	/**
	 * @return HttpParams
	 */
	function Input(): HttpParams
	{
		return request()->params;
	}

}

if (!function_exists('Server')) {


	/**
	 * @return Http|Packet|Receive|Websocket|null
	 * @throws Exception
	 */
	function Server(): Http|Packet|Receive|Websocket|null
	{
		return Snowflake::app()->getSwoole();
	}

}

if (!function_exists('storage')) {

	/**
	 * @param string $fileName
	 * @param string $path
	 * @return string
	 * @throws Exception
	 */
	function storage($fileName = '', $path = ''): string
	{

		$basePath = rtrim(Snowflake::getStoragePath(), '/');
		if (!empty($path)) {
			$path = ltrim($path, '/');
			if (!is_dir($basePath . '/' . $path)) {
				mkdir($basePath . '/' . $path, 0777, true);
			}
		}
		if (empty($fileName)) {
			return $basePath . '/' . $path . '/';
		}
		$fileName = $basePath . '/' . $path . '/' . $fileName;
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
	function initDir($path): bool|string
	{
		return mkdir($path, 0777, true);
	}


}


if (!function_exists('listen')) {


	/**
	 * @param $name
	 * @param $callback
	 * @param $params
	 * @param $isAppend
	 * @throws Exception
	 * @throws Exception
	 */
	function listen($name, $callback, $params = [], $isAppend = true)
	{
		$event = Snowflake::app()->getEvent();
		$event->on($name, $callback, $params, $isAppend);
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
	 * @param int $pid
	 * @throws ConfigException
	 * @throws Exception
	 */
	function name(int $pid)
	{
		if (Snowflake::getPlatform()->isMac()) {
			return;
		}

		$name = Config::get('id', false, 'system') . '[' . $pid . ']';

		swoole_set_process_name($name);
	}

}

if (!function_exists('response')) {

	/**
	 * @return Response|stdClass
	 * @throws
	 */
	function response(): Response|stdClass
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
	 * @return mixed
	 * @throws Exception
	 */
	function send($context, $statusCode = 404): mixed
	{
		return \response()->send($context, $statusCode);
	}

}


if (!function_exists('zero_full')) {
	function zero_full(int $data = 1, int $length = 10): string
	{
		return sprintf('%0' . $length . 'd', $data);
	}
}


if (!function_exists('redirect')) {


	/**
	 * @param $url
	 * @return int
	 */
	function redirect($url): int
	{
		return response()->redirect($url);
	}

}


if (!function_exists('env')) {

	/**
	 * @param $key
	 * @param null $default
	 * @return array|string|null
	 */
	#[Pure] function env($key, $default = null): null|array|string
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
	function sweep($configPath = APP_PATH . 'config'): bool|array|string|null
	{
		$array = [];
		foreach (glob($configPath . '/*') as $config) {
			$array = array_merge(require_once "$config", $array);
		}
		return $array;
	}

}


if (!function_exists('swoole_serialize')) {


	/**
	 * @param $data
	 * @return string
	 */
	function swoole_serialize($data): string
	{
//        if (class_exists('swoole_serialize')) {
//            return \swoole_serialize::pack($data);
//        } else {
		return serialize($data);
//        }
	}

}


if (!function_exists('swoole_unserialize')) {


	/**
	 * @param $data
	 * @return string
	 */
	function swoole_unserialize($data): mixed
	{
		if (empty($data)) {
			return null;
		}
//        if (class_exists('swoole_serialize')) {
//            return \swoole_serialize::unpack($data);
//        } else {
		return unserialize($data);
//        }
	}

}


if (!function_exists('merge')) {


	/**
	 * @param $param
	 * @param $param1
	 * @return array
	 */
	function merge($param, $param1): array
	{
		return ArrayAccess::merge($param, $param1);
	}

}


if (!function_exists('router')) {


	/**
	 * @return Router
	 * @throws Exception
	 */
	function router(): Router
	{
		return Snowflake::app()->getRouter();
	}

}


if (!function_exists('jTraceEx')) {

	/**
	 * @param $e
	 * @param null $seen
	 * @return string
	 */
	function jTraceEx($e, $seen = null): string
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


if (!function_exists('swoole_substr_json_decode')) {


	/**
	 * @param $packet
	 * @param int $length
	 * @return mixed
	 */
	function swoole_substr_json_decode($packet, $length = 0): mixed
	{
		return json_decode($packet, true);
	}

}


if (!function_exists('swoole_substr_unserialize')) {


	/**
	 * @param $packet
	 * @param int $length
	 * @return mixed
	 */
	function swoole_substr_unserialize($packet, $length = 0): mixed
	{
		return unserialize($packet);
	}

}
