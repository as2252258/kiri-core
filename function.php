<?php

defined('APP_PATH') or define('APP_PATH', realpath(__DIR__ . '/../../'));


use Annotation\Annotation;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\Route\Router;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Config;
use Snowflake\Application;
use Snowflake\Core\ArrayAccess;
use Snowflake\Core\Json;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\WebSocket\Server;

if (!function_exists('make')) {


	/**
	 * @param $name
	 * @param $default
	 * @return mixed
	 * @throws
	 */
	function make($name, $default = null): mixed
	{
		if (class_exists($name)) {
			return Snowflake::createObject($name);
		}
		if (Snowflake::has($name)) {
			return Snowflake::app()->get($name);
		}
		if (empty($default)) {
			throw new Exception("Unknown component ID: $name");
		}
		if (Snowflake::has($default)) {
			return Snowflake::app()->get($default);
		}
		$class = Snowflake::createObject($default);
		class_alias($name, $default, true);
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
		return Snowflake::getAnnotation();
	}


}


if (!function_exists('scan_directory')) {


	/**
	 * @param $dir
	 * @param $namespace
	 * @throws Exception
	 */
	function scan_directory($dir, $namespace)
	{
		$annotation = Snowflake::app()->getAnnotation();
		$annotation->read($dir, $namespace);
		$annotation->runtime($dir, $namespace);
	}

}


if (!function_exists('swoole')) {


	/**
	 * @return Server|null
	 * @throws Exception
	 */
	function swoole(): ?Server
	{
		return Snowflake::getWebSocket();
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
	function write(string $messages, string $category = 'app')
	{
		$logger = Snowflake::app()->getLogger();
		$logger->write($messages, $category);
	}
}


if (!function_exists('redis')) {


	/**
	 * @return \Snowflake\Cache\Redis|Redis
	 * @throws Exception
	 */
	function redis(): \Snowflake\Cache\Redis|Redis
	{
		return Snowflake::app()->getRedis();
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
		Event::trigger($event, $params);
	}
}

if (!function_exists('aop')) {


	/**
	 * @param mixed $handler
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	function aop(mixed $handler, array $params = []): mixed
	{
		return Snowflake::app()->get('aop')->dispatch($handler, $params);
	}
}


if (!function_exists('app')) {


	/**
	 * @return Application|null
	 */
	#[Pure] function app(): ?Application
	{
		return Snowflake::app();
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


if (!function_exists('trim_blank')) {


	/**
	 * 空白字符替换
	 * @param string $content 内容
	 * @param int $len 截取长度
	 * @param string $encode 编码
	 * @param bool $htmlTags
	 * @return array|string|null
	 */
	function trim_blank(string $content, int $len = 0, string $encode = 'utf-8', bool $htmlTags = true): array|string|null
	{
		$str = trim($content);
		if ($htmlTags) {
			$str = strip_tags($str);
		}
		$str = preg_replace('/[\n|\r|\t]+/', '', $str);
		$str = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", '', $str);
		if ($len > 0) {
			return mb_substr($str, 0, $len, $encode);
		} else {
			return $str;
		}
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
	 * @throws Exception
	 */
	function request(): Request
	{
		return Snowflake::getFactory()->get('request');
	}

}

if (!function_exists('Input')) {

	/**
	 * @return HttpParams
	 * @throws Exception
	 */
	function Input(): HttpParams
	{
		return request()->params;
	}

}

if (!function_exists('storage')) {

	/**
	 * @param string|null $fileName
	 * @param string|null $path
	 * @return string
	 * @throws Exception
	 */
	function storage(?string $fileName = '', ?string $path = ''): string
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
}


if (!function_exists('event')) {


	/**
	 * @param $name
	 * @param $callback
	 * @param array $params
	 * @param bool $isAppend
	 * @throws Exception
	 * @throws Exception
	 */
	function event($name, $callback, array $params = [], bool $isAppend = true)
	{
		Event::on($name, $callback, $params, $isAppend);
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
	 * @param string|null $prefix
	 * @throws ConfigException
	 * @throws Exception
	 */
	function name(int $pid, string $prefix = null)
	{
		if (Snowflake::getPlatform()->isMac()) {
			return;
		}

		$name = Config::get('id', 'system') . '[' . $pid . ']';
		if (!empty($prefix)) {
			$name .= '.' . $prefix;
		}
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
		return Snowflake::app()->get('response');
	}

}

if (!function_exists('send')) {

	/**
	 * @param $context
	 * @param int $statusCode
	 * @return mixed
	 * @throws Exception
	 */
	function send($context, int $statusCode = 404): mixed
	{
		if (is_array($context)) $context = Json::encode($context);

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


if (!function_exists('di')) {


	/**
	 * @param string $className
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	function di(string $className): mixed
	{
		return Snowflake::getDi()->get($className);
	}

}

if (!function_exists('duplicate')) {


	/**
	 * @param string $className
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	function duplicate(string $className): mixed
	{
		$class = di($className);
		return clone $class;
	}

}

if (!function_exists('sweep')) {

	/**
	 * @param string $configPath
	 * @return array|false|string|null
	 */
	function sweep(string $configPath = APP_PATH . 'config'): bool|array|string|null
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


if (!function_exists('isService')) {


	/**
	 * @param string $name
	 * @return bool
	 */
	#[Pure] function isService(string $name): bool
	{
		return Snowflake::app()->has($name);
	}

}

if (!function_exists('getService')) {


	/**
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	function getService(string $name): mixed
	{
		return Snowflake::app()->get($name);
	}

}


if (!function_exists('jTraceEx')) {

	/**
	 * @param $e
	 * @param null $seen
	 * @return string
	 */
	function jTraceEx($e, $seen = null, $toHtml = false): string
	{
		$starter = $seen ? 'Caused by: ' : '';
		$result = array();
		if (!$seen) $seen = array();
		$trace = $e->getTrace();
		$prev = $e->getPrevious();
		$result[] = sprintf('%s%s: %s', $starter, $e::class, $e->getMessage());
		$file = $e->getFile();
		$line = $e->getLine();

		foreach ($trace as $value) {
			$result[] = sprintf(' at %s%s%s(%s%s%s)',
				count($value) && array_key_exists('class', $value) ? str_replace('\\', '.', $value['class']) : '',
				count($value) && array_key_exists('class', $value) && array_key_exists('function', $value) ? '.' : '',
				count($value) && array_key_exists('function', $value) ? str_replace('\\', '.', $value['function']) : '(main)',
				$line === null ? $file : basename($file),
				$line === null ? '' : ':',
				$line === null ? '' : $line);

			$file = array_key_exists('file', $value) ? $value['file'] : 'Unknown Source';
			$line = array_key_exists('file', $value) && array_key_exists('line', $value) && $value['line'] ? $value['line'] : null;
		}
		$result = join($toHtml ? "<br>" : "\n", $result);
		if ($prev) {
			$result .= ($toHtml ? "<br>" : "\n") . jTraceEx($prev, $seen, $toHtml);
		}

		return $result;
	}


}


if (!function_exists('swoole_substr_json_decode')) {


	/**
	 * @param $packet
	 * @param int $length
	 * @return mixed
	 */
	function swoole_substr_json_decode($packet, int $length = 0): mixed
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
	function swoole_substr_unserialize($packet, int $length = 0): mixed
	{
		return unserialize($packet);
	}

}


if (!function_exists('debug')) {

	/**
	 * @param mixed $message
	 * @param string $method
	 * @throws Exception
	 */
	function debug(mixed $message, string $method = 'app')
	{
		Snowflake::app()->debug($message, $method);
	}

}

if (!function_exists('error')) {

	/**
	 * @param mixed $message
	 * @param string $method
	 * @throws Exception
	 */
	function error(mixed $message, string $method = 'error')
	{
		Snowflake::app()->error($message, $method);
	}
}

if (!function_exists('success')) {

	/**
	 * @param mixed $message
	 * @param string $method
	 * @throws Exception
	 */
	function success(mixed $message, string $method = 'app')
	{
		Snowflake::app()->success($message, $method);
	}
}

