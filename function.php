<?php

defined('APP_PATH') or define('APP_PATH', realpath(__DIR__ . '/../../'));


use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Config;
use Kiri\Annotation\Annotation;
use Kiri\Annotation\Route\Route;
use Kiri\Application;
use Kiri\Core\ArrayAccess;
use Kiri\Di\NoteManager;
use Kiri\Error\Logger;
use Kiri\Events\EventDispatch;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Message\Handler\Router;
use Psr\Log\LoggerInterface;
use Swoole\Process;
use Swoole\WebSocket\Server;

if (!function_exists('make')) {


	/**
	 * @param $name
	 * @param $default
	 * @return mixed
	 * @throws
	 */
	function make($name, $default = NULL): mixed
	{
		if (class_exists($name)) {
			return Kiri::createObject($name);
		}
		if (Kiri::has($name)) {
			return Kiri::app()->get($name);
		}
		if (empty($default)) {
			throw new Exception("Unknown component ID: $name");
		}
		if (Kiri::has($default)) {
			return Kiri::app()->get($default);
		}
		$class = Kiri::createObject($default);
		class_alias($name, $default, TRUE);
		return $class;
	}


}


if (!function_exists('map')) {


	/**
	 * @param array $params
	 * @param Closure $closure
	 * @return mixed
	 */
	function map(array $params, Closure $closure): mixed
	{
		return array_map($closure, $params);
	}

}


if (!function_exists('checkPortIsAlready')) {


	/**
	 * @param $port
	 * @return bool|string
	 * @throws Exception
	 */
	function checkPortIsAlready($port): bool|string
	{
		if (!Kiri::getPlatform()->isLinux()) {
			exec("lsof -i :" . $port . " | grep -i 'LISTEN' | awk '{print $2}'", $output);
			if (empty($output)) return FALSE;
			$output = explode(PHP_EOL, $output[0]);
			return $output[0];
		}

		$serverPid = file_get_contents(storage('.swoole.pid'));
		if (!empty($serverPid) && shell_exec('ps -ef | grep ' . $serverPid . ' | grep -v grep')) {
			Process::kill($serverPid, 0) && Process::kill($serverPid, SIGTERM);
		}

		exec('netstat -lnp | grep ' . $port . ' | grep "LISTEN" | awk \'{print $7}\'', $output);
		if (empty($output)) {
			return FALSE;
		}
		return explode('/', $output[0])[0];
	}

}


if (!function_exists('done')) {

	/**
	 *
	 */
	function done()
	{
		set_env('state', 'exit');
	}

}


if (!function_exists('set_env')) {


	/**
	 * @param $key
	 * @param $value
	 */
	function set_env($key, $value)
	{
		putenv(sprintf('%s=%s', $key, $value));
	}

}

if (!function_exists('enable_file_modification_listening')) {


	function enable_file_modification_listening(): void
	{
		putenv('enable_file_modification_listening=on');
	}


}


if (!function_exists('is_enable_file_modification_listening')) {


	/**
	 * @return bool
	 */
	#[Pure] function is_enable_file_modification_listening(): bool
	{
		return env('enable_file_modification_listening', 'off') == 'on';
	}


}

if (!function_exists('disable_file_modification_listening')) {


	function disable_file_modification_listening()
	{
		putenv('enable_file_modification_listening=off');
	}


}


if (!function_exists('now')) {

	/**
	 * @return string
	 */
	function now(): string
	{
		return date('Y-m-d H:i:s') . '.' . str_replace(time() . '.', '', (string)microtime(TRUE));
	}
}


if (!function_exists('workerName')) {


	/**
	 * @param $worker_id
	 * @return string
	 */
	function workerName($worker_id)
	{
		return $worker_id >= Kiri::app()->getSwoole()->setting['worker_num'] ? 'Task' : 'Worker';
	}

}


if (!function_exists('Annotation')) {


	/**
	 * @return Annotation
	 * @throws Exception
	 */
	function annotation(): Annotation
	{
		return Kiri::getAnnotation();
	}


}


if (!function_exists('scan_directory')) {


	/**
	 * @param $dir
	 * @param $namespace
	 * @param array $exclude
	 * @throws ReflectionException
	 * @throws Exception
	 */
	function scan_directory($dir, $namespace, array $exclude = [])
	{
		$annotation = Kiri::app()->getAnnotation();
		$annotation->read($dir, $namespace, $exclude);

		injectRuntime($dir, $exclude);
	}

}

if (!class_exists('ReturnTypeWillChange')) {

	/**
	 * @since 8.1
	 */
	#[Attribute(Attribute::TARGET_METHOD)]
	final class ReturnTypeWillChange
	{
		public function __construct()
		{
		}
	}


}


if (!function_exists('injectRuntime')) {


	/**
	 * @param string $path
	 * @param array $exclude
	 * @throws ReflectionException
	 * @throws Exception
	 */
	function injectRuntime(string $path, array $exclude = [])
	{
		$fileLists = Kiri::getAnnotation()->runtime($path, $exclude);
		$di = Kiri::getDi();

		$router = [];
		foreach ($fileLists as $class) {
			foreach (NoteManager::getTargetAnnotation($class) as $value) {
				if (!method_exists($value, 'execute')) {
					continue;
				}
				$value->execute($class);
			}
			$methods = $di->getMethodAttribute($class);
			foreach ($methods as $method => $attribute) {
				if (empty($attribute)) {
					continue;
				}
				foreach ($attribute as $item) {
					if ($item instanceof Route) {
						$router[] = [$item, $class, $method];
					} else {
						if (!method_exists($item, 'execute')) {
							continue;
						}
						$item->execute($class, $method);
					}
				}
			}
		}
		if (!empty($router)) {
			foreach ($router as $class) {
				[$item, $class, $method] = $class;
				if (!method_exists($item, 'execute')) {
					continue;
				}
				$item->execute($class, $method);
			}
		}
	}

}


if (!function_exists('swoole')) {


	/**
	 * @return Server|null
	 * @throws Exception
	 */
	function swoole(): ?Server
	{
		return Kiri::getWebSocket();
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
	function isUrl($url, bool $get_info = TRUE): bool|array
	{
		if (str_starts_with($url, '/')) {
			return FALSE;
		}
		$queryMatch = '/((http[s]?):\/\/)?(([\w\-\_]+\.)+\w+(:\d+)?)(\/.*)?/';
		if (!preg_match($queryMatch, $url, $outPut)) {
			return FALSE;
		}

		[$scheme, $host, $port, $user, $pass, $query, $path, $fragment] = parse_url($url);
		if ($scheme == 'https' && empty($port)) {
			$port = 443;
		}

		if (!empty($query)) $path .= '?' . $query;
		if (!empty($fragment)) $path .= '#' . $fragment;


		unset($outPut);

		return [$scheme == 'https', $host, $port, $path];
	}

}


if (!function_exists('split_request_uri')) {


	/**
	 * @param $url
	 * @return false|array
	 */
	function split_request_uri($url): bool|array
	{
		if (($parse = isUrl($url, NULL)) === FALSE) {
			return FALSE;
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
		return !is_array($param) ? FALSE : $param[0];
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
				if ($pos === FALSE || strlen($value) - 4 != $pos) {
					continue;
				}

				$replace = ltrim(str_replace(__DIR__, '', $value), '/');
				$replace = str_replace('.php', '', $replace);

				$first = explode(DIRECTORY_SEPARATOR, $replace);
				array_shift($first);

				Kiri::setAutoload($namespace . '\\' . implode('\\', $first), $value);
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
		$logger = Kiri::app()->getLogger();
		$logger->write($messages, $category);
	}
}


if (!function_exists('redis')) {


	/**
	 * @return \Kiri\Cache\Redis|Redis
	 * @throws Exception
	 */
	function redis(): \Kiri\Cache\Redis|Redis
	{
		return Kiri::getDi()->get(\Kiri\Cache\Redis::class);
	}
}

if (!function_exists('fire')) {


	/**
	 * @param object $event
	 */
	function fire(object $event)
	{
		di(EventDispatch::class)->dispatch($event);
	}
}


if (!function_exists('app')) {


	/**
	 * @return Application|null
	 */
	#[Pure] function app(): ?Application
	{
		return Kiri::app();
	}

}

if (!function_exists('instance_load')) {

	function instance_load()
	{
		$content = json_decode(file_get_contents(__DIR__ . '/composer.json'), TRUE);
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
		return Kiri::app()->getLogger();
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
	function trim_blank(string $content, int $len = 0, string $encode = 'utf-8', bool $htmlTags = TRUE): array|string|null
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

	function get_file_extension($filename): bool|int|string
	{
		$mime_types = [
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


			'323'     => 'text / h323',
			'acx'     => 'application/internet-property-stream',
			'aif'     => 'audio/x-aiff',
			'aifc'    => 'audio/x-aiff',
			'aiff'    => 'audio/x-aiff',
			'asf'     => 'video/x-ms-asf',
			'asr'     => 'video/x-ms-asf',
			'asx'     => 'video/x-ms-asf',
			'au'      => 'audio/basic',
			'avi'     => 'video/x-msvideo',
			'axs'     => 'application/olescript',
			'bas'     => 'text/plain',
			'bcpio'   => 'application/x-bcpio',
			'bin'     => 'application/octet-stream',
			'c'       => 'text/plain',
			'cat'     => 'application/vnd.ms-pkiseccat',
			'cdf'     => 'application/x-cdf',
			'cer'     => 'application/x-x509-ca-cert',
			'class'   => 'application/octet-stream',
			'clp'     => 'application/x-msclip',
			'cmx'     => 'image/x-cmx',
			'cod'     => 'image/cis-cod',
			'cpio'    => 'application/x-cpio',
			'crd'     => 'application/x-mscardfile',
			'crl'     => 'application/pkix-crl',
			'crt'     => 'application/x-x509-ca-cert',
			'csh'     => 'application/x-csh',
			'dcr'     => 'application/x-director',
			'der'     => 'application/x-x509-ca-cert',
			'dir'     => 'application/x-director',
			'dll'     => 'application/x-msdownload',
			'dms'     => 'application/octet-stream',
			'dot'     => 'application/msword',
			'dvi'     => 'application/x-dvi',
			'dxr'     => 'application/x-director',
			'etx'     => 'text/x-setext',
			'evy'     => 'application/envoy',
			'fif'     => 'application/fractals',
			'flr'     => 'x-world/x-vrml',
			'gtar'    => 'application/x-gtar',
			'gz'      => 'application/x-gzip',
			'h'       => 'text/plain',
			'hdf'     => 'application/x-hdf',
			'hlp'     => 'application/winhlp',
			'hqx'     => 'application/mac-binhex40',
			'hta'     => 'application/hta',
			'htc'     => 'text/x-component',
			'htt'     => 'text/webviewhtml',
			'ief'     => 'image/ief',
			'iii'     => 'application/x-iphone',
			'ins'     => 'application/x-internet-signup',
			'isp'     => 'application/x-internet-signup',
			'jfif'    => 'image/pipeg',
			'jpe'     => 'image/jpeg',
			'jpg'     => 'image/jpeg',
			'latex'   => 'application/x-latex',
			'lha'     => 'application/octet-stream',
			'lsf'     => 'video/x-la-asf',
			'lsx'     => 'video/x-la-asf',
			'lzh'     => 'application/octet-stream',
			'm13'     => 'application/x-msmediaview',
			'm14'     => 'application/x-msmediaview',
			'm3u'     => 'audio/x-mpegurl',
			'man'     => 'application/x-troff-man',
			'mdb'     => 'application/x-msaccess',
			'me'      => 'application/x-troff-me',
			'mht'     => 'message/rfc822',
			'mhtml'   => 'message/rfc822',
			'mid'     => 'audio/mid',
			'mny'     => 'application/x-msmoney',
			'movie'   => 'video/x-sgi-movie',
			'mp2'     => 'video/mpeg',
			'mpa'     => 'video/mpeg',
			'mpe'     => 'video/mpeg',
			'mpeg'    => 'video/mpeg',
			'mpg'     => 'video/mpeg',
			'mpp'     => 'application/vnd.ms-project',
			'mpv2'    => 'video/mpeg',
			'ms'      => 'application/x-troff-ms',
			'mvb'     => 'application/x-msmediaview',
			'nws'     => 'message/rfc822',
			'oda'     => 'application/oda',
			'p10'     => 'application/pkcs10',
			'p12'     => 'application/x-pkcs12',
			'p7b'     => 'application/x-pkcs7-certificates',
			'p7c'     => 'application/x-pkcs7-mime',
			'p7m'     => 'application/x-pkcs7-mime',
			'p7r'     => 'application/x-pkcs7-certreqresp',
			'p7s'     => 'application/x-pkcs7-signature',
			'pbm'     => 'image/x-portable-bitmap',
			'pfx'     => 'application/x-pkcs12',
			'pgm'     => 'image/x-portable-graymap',
			'pko'     => 'application/ynd.ms-pkipko',
			'pma'     => 'application/x-perfmon',
			'pmc'     => 'application/x-perfmon',
			'pml'     => 'application/x-perfmon',
			'pmr'     => 'application/x-perfmon',
			'pmw'     => 'application/x-perfmon',
			'pnm'     => 'image/x-portable-anymap',
			'pot'     => 'application/vnd.ms-powerpoint',
			'ppm'     => 'image/x-portable-pixmap',
			'pps'     => 'application/vnd.ms-powerpoint',
			'prf'     => 'application/pics-rules',
			'pub'     => 'application/x-mspublisher',
			'ra'      => 'audio/x-pn-realaudio',
			'ram'     => 'audio/x-pn-realaudio',
			'ras'     => 'image/x-cmu-raster',
			'rgb'     => 'image/x-rgb',
			'rmi'     => 'audio/mid',
			'roff'    => 'application/x-troff',
			'rtx'     => 'text/richtext',
			'scd'     => 'application/x-msschedule',
			'sct'     => 'text/scriptlet',
			'setpay'  => 'application/set-payment-initiation',
			'setreg'  => 'application/set-registration-initiation',
			'sh'      => 'application/x-sh',
			'shar'    => 'application/x-shar',
			'sit'     => 'application/x-stuffit',
			'snd'     => 'audio/basic',
			'spc'     => 'application/x-pkcs7-certificates',
			'spl'     => 'application/futuresplash',
			'src'     => 'application/x-wais-source',
			'sst'     => 'application/vnd.ms-pkicertstore',
			'stl'     => 'application/vnd.ms-pkistl',
			'stm'     => 'text/html',
			'sv4cpio' => 'application/x-sv4cpio',
			'sv4crc'  => 'application/x-sv4crc',
			't'       => 'application/x-troff',
			'tar'     => 'application/x-tar',
			'tcl'     => 'application/x-tcl',
			'tex'     => 'application/x-tex',
			'texi'    => 'application/x-texinfo',
			'texinfo' => 'application/x-texinfo',
			'tgz'     => 'application/x-compressed',
			'tif'     => 'image/tiff',
			'tr'      => 'application/x-troff',
			'trm'     => 'application/x-msterminal',
			'tsv'     => 'text/tab-separated-values',
			'uls'     => 'text/iuls',
			'ustar'   => 'application/x-ustar',
			'vcf'     => 'text/x-vcard',
			'vrml'    => 'x-world/x-vrml',
			'wav'     => 'audio/x-wav',
			'wcm'     => 'application/vnd.ms-works',
			'wdb'     => 'application/vnd.ms-works',
			'wks'     => 'application/vnd.ms-works',
			'wmf'     => 'application/x-msmetafile',
			'wps'     => 'application/vnd.ms-works',
			'wri'     => 'application/x-mswrite',
			'wrl'     => 'x-world/x-vrml',
			'wrz'     => 'x-world/x-vrml',
			'xaf'     => 'x-world/x-vrml',
			'xbm'     => 'image/x-xbitmap',
			'xla'     => 'application/vnd.ms-excel',
			'xlc'     => 'application/vnd.ms-excel',
			'xlm'     => 'application/vnd.ms-excel',
			'xlt'     => 'application/vnd.ms-excel',
			'xlw'     => 'application/vnd.ms-excel',
			'xof'     => 'x-world/x-vrml',
			'xpm'     => 'image/x-xpixmap',
			'xwd'     => 'image/x-xwindowdump',
			'z'       => 'application/x-compress',
		];

		$explode = explode('.', $filename);
		$ext = strtolower(array_pop($explode));
		if (array_key_exists($ext, $mime_types)) {
			return $ext;
		} else if (function_exists('finfo_open')) {
			$fInfo = finfo_open(FILEINFO_MIME);
			$mimeType = finfo_file($fInfo, $filename);
			finfo_close($fInfo);
			$mimeType = current(explode('; ', $mimeType));
			if (($search = array_search($mimeType, $mime_types)) == FALSE) {
				return $mimeType;
			}
			return $search;
		} else {
			return 'application/octet-stream';
		}
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

		$basePath = rtrim(Kiri::getStoragePath(), '/');
		if (!empty($path)) {
			$path = ltrim($path, '/');
			if (!is_dir($basePath . '/' . $path)) {
				mkdir($basePath . '/' . $path, 0777, TRUE);
			}
		}
		if (empty($fileName)) {
			return $basePath . '/' . $path;
		}
		$fileName = $basePath . '/' . $path . $fileName;
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
	 * @param bool $isAppend
	 * @throws Exception
	 */
	function event($name, $callback, bool $isAppend = TRUE)
	{
		$pro = di(EventProvider::class);
		$pro->on($name, $callback, 0);
	}

}


if (!function_exists('name')) {

	/**
	 * @param int $pid
	 * @param string|null $prefix
	 * @throws ConfigException
	 * @throws Exception
	 */
	function name(int $pid, string $prefix = NULL)
	{
		if (Kiri::getPlatform()->isMac()) {
			return;
		}

		$name = Config::get('id', 'system') . '[' . $pid . ']';
		if (!empty($prefix)) {
			$name .= '.' . $prefix;
		}
		swoole_set_process_name($name);
	}

}


if (!function_exists('zero_full')) {
	function zero_full(int $data = 1, int $length = 10): string
	{
		return sprintf('%0' . $length . 'd', $data);
	}
}


if (!function_exists('env')) {

	/**
	 * @param $key
	 * @param null $default
	 * @return array|string|null
	 */
	#[Pure] function env($key, $default = NULL): null|array|string
	{
		$env = getenv($key);
		if ($env === FALSE) {
			return $default;
		}
		return $env;
	}

}


if (!function_exists('di')) {


	/**
	 * @param string $className
	 * @return mixed
	 */
	function di(string $className): mixed
	{
		return Kiri::getDi()->get($className);
	}

}


if (!function_exists('interval')) {


	/**
	 * @param callable $callback
	 * @param int $interval
	 * @param bool $is
	 */
	function interval(callable $callback, int $interval = 1000, bool $is = FALSE)
	{
		usleep($interval * 1000);

		$callback();

		interval($callback, $interval, $is);
	}

}


if (!function_exists('duplicate')) {


	/**
	 * @param string $className
	 * @return mixed
	 * @throws ReflectionException
	 */
	function duplicate(string $className): mixed
	{
		$class = di($className);
		$clone = clone $class;
		if (method_exists($clone, 'clear')) {
			$clone->clear();
		}
		return $clone;
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
			return NULL;
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
		return Kiri::getDi()->get(Router::class);
	}

}


if (!function_exists('isService')) {


	/**
	 * @param string $name
	 * @return bool
	 */
	function isService(string $name): bool
	{
		return Kiri::app()->has($name);
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
		return Kiri::app()->get($name);
	}

}


if (!function_exists('jTraceEx')) {

	/**
	 * @param $e
	 * @param null $seen
	 * @param bool $toHtml
	 * @return string
	 */
	function jTraceEx($e, $seen = NULL, bool $toHtml = FALSE): string
	{
		$starter = $seen ? 'Caused by: ' : '';
		$result = [];
		if (!$seen) $seen = [];
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
				$line === NULL ? $file : basename($file),
				$line === NULL ? '' : ':',
				$line === NULL ? '' : $line);

			$file = array_key_exists('file', $value) ? $value['file'] : 'Unknown Source';
			$line = array_key_exists('file', $value) && array_key_exists('line', $value) && $value['line'] ? $value['line'] : NULL;
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
		return json_decode($packet, TRUE);
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
		Kiri::app()->debug($message, $method);
	}

}

if (!function_exists('info')) {

	/**
	 * @param mixed $message
	 * @param string $method
	 * @throws Exception
	 */
	function info(mixed $message, string $method = 'app')
	{
		Kiri::app()->info($message, $method);
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
		Kiri::getDi()->get(LoggerInterface::class)->error($method, [$message]);
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
		Kiri::app()->success($message, $method);
	}
}


if (!function_exists('error_trigger_format')) {


	/**
	 * @param Throwable|Error $throwable
	 * @return string
	 */
	function error_trigger_format(\Throwable|\Error $throwable): string
	{
		$message = "\n\tThrowable: " . $throwable->getMessage() . "\t\n\t" . '	' . $throwable->getFile() . " at line " . $throwable->getLine();

		$message .= "\n\t   trance";
		foreach ($throwable->getTrace() as $value) {
			if (!isset($value['file'])) {
				continue;
			}
			$message .= "\n\t" . $value['file'] . " -> " . $value['line'] . "(" . (isset($value['class']) ? $value['class'] . '::' : '') . ($value['function'] ?? 'Closure') . ")";
		}
		return "\033[41;37m" . $message . "\033[0m";
	}

}

