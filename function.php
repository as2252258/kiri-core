<?php

defined('APP_PATH') or define('APP_PATH', realpath(__DIR__ . '/../../'));


use JetBrains\PhpStorm\Pure;
use Kiri\Config\ConfigProvider;
use Kiri\Core\ArrayAccess;
use Kiri\Di\Context;
use Kiri\Events\EventDispatch;
use Kiri\Events\EventProvider;
use Kiri\Router\Request;
use Kiri\Router\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Swoole\Process;

if (!function_exists('make')) {


    /**
     * @param $name
     * @param $default
     * @return mixed
     * @throws
     */
    function make($name, $default = NULL): mixed
    {
        if (!class_exists($name) && !interface_exists($name)) {
            return Kiri::getDi()->get($default);
        }
        return Kiri::getDi()->get($name);
    }

}


if (!function_exists('isJson')) {


    function isJson(?string $string): bool
    {
        if (is_null($string)) return false;
        return (str_starts_with($string, '{') && str_ends_with($string, '}'))
               || (str_ends_with($string, '[') && str_starts_with($string, ']'));
    }

}

if (!function_exists('instance')) {


    /**
     * @param $class
     * @param array $constrict
     * @param array $config
     * @return null|object
     * @throws
     */
    function instance($class, array $constrict = [], array $config = []): ?object
    {
        return Kiri::getDi()->make($class, $constrict, $config);
    }


}


if (!function_exists('call')) {


    /**
     * @param $handler
     * @param mixed ...$params
     * @return mixed
     * @throws Exception
     */
    function call($handler, ...$params): mixed
    {
        if (is_array($handler) && is_string($handler[0])) {
            $handler[0] = di($handler[0]);
        }
        if (!is_callable($handler, true)) {
            throw new Exception('Call function not exists.');
        }
        return call_user_func($handler, ...$params);
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


if (!function_exists('set_env')) {


    /**
     * @param $key
     * @param $value
     */
    function set_env($key, $value): void
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


    function disable_file_modification_listening(): void
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


if (!function_exists('directory')) {

    /**
     * @param $name
     * @return string
     */
    function directory($name): string
    {
        return realpath(APP_PATH . $name);
    }


}


if (!function_exists('msgpack_pack')) {


    /**
     * @param $content
     * @return string
     */
    function msgpack_pack($content): string
    {
        return serialize($content);
    }

}
if (!function_exists('msgpack_unpack')) {


    /**
     * @param $content
     * @return mixed
     */
    function msgpack_unpack($content): mixed
    {
        return unserialize($content);
    }

}

if (!function_exists('request')) {


    /**
     * @return Request
     * @throws
     */
    function request(): RequestInterface
    {
        $request = Kiri::getDi()->get(RequestInterface::class);

        return Context::get(RequestInterface::class, $request);
    }

}


if (!function_exists('response')) {


    /**
     * @return Response
     * @throws
     */
    function response(): ResponseInterface
    {
        $data = Kiri::getDi()->get(ResponseInterface::class);

        return Context::get(ResponseInterface::class, $data);
    }

}


if (!function_exists('redis')) {


    /**
     * @return \Kiri\Redis\Redis|Redis
     * @throws Exception
     */
    function redis(): \Kiri\Redis\Redis|Redis
    {
        return Kiri::getDi()->get(\Kiri\Redis\Redis::class);
    }
}

if (!function_exists('fire')) {


    /**
     * @param object $event
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    function fire(object $event): void
    {
        di(EventDispatch::class)->dispatch($event);
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
        $ext     = strtolower(array_pop($explode));
        if (array_key_exists($ext, $mime_types)) {
            return $ext;
        } else if (function_exists('finfo_open')) {
            $fInfo    = finfo_open(FILEINFO_MIME);
            $mimeType = finfo_file($fInfo, $filename);
            finfo_close($fInfo);
            $mimeType = current(explode('; ', $mimeType));
            if (!($search = array_search($mimeType, $mime_types))) {
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


if (!function_exists('on')) {


    /**
     * @param $name
     * @param $callback
     * @param int $index
     * @throws
     */
    function on($name, $callback, int $index = 0): void
    {
        $pro = di(EventProvider::class);
        $pro->on($name, $callback, $index);
    }

}


if (!function_exists('off')) {


    /**
     * @param $name
     * @param $callback
     * @throws
     */
    function off($name, $callback): void
    {
        $pro = di(EventProvider::class);
        $pro->off($name, $callback);
    }

}


if (!function_exists('process_name_set')) {

    /**
     * @param int $pid
     * @param string|null $prefix
     * @throws Exception
     */
    function process_name_set(int $pid, string $prefix = NULL): void
    {
        if (Kiri::getPlatform()->isMac()) {
            return;
        }

        $name = \config('id', 'system') . '[' . $pid . ']';
        if (!empty($prefix)) {
            $name .= '.' . $prefix;
        }
        swoole_set_process_name($name);
    }

}


if (!function_exists('zero_full')) {

    /**
     * @param int $data
     * @param int $length
     * @return string
     */
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
    function env($key, $default = NULL): null|array|string
    {
        $env = getenv($key);
        if ($env === FALSE) {
            return $default;
        }
        return $env;
    }

}


if (!function_exists('config')) {

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    function config($key, $default = NULL): mixed
    {
        return make(ConfigProvider::class)->get($key, $default);
    }

}


if (!function_exists('created')) {

    /**
     * @param $key
     * @param array $construct
     * @param array $config
     * @return null|object
     * @throws ReflectionException
     */
    function created($key, array $construct = [], array $config = []): ?object
    {
        return Kiri::getDi()->make($key, $construct, $config);
    }

}


if (!function_exists('di')) {


    /**
     * @param string|object $className
     * @return mixed
     * @throws ReflectionException
     */
    function di(string|object $className): mixed
    {
        if (is_object($className)) {
            return $className;
        }
        return Kiri::getDi()->get($className);
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
        foreach (glob($configPath . '/*.php') as $config) {
            $array = array_merge(require "$config", $array);
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
    function merge($param, $param1): array
    {
        return ArrayAccess::merge($param, $param1);
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
        $result  = [];
        if (!$seen) $seen = [];
        $trace    = $e->getTrace();
        $prev     = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, $e::class, $e->getMessage());
        $file     = $e->getFile();
        $line     = $e->getLine();

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

if (!function_exists('error')) {

    /**
     * @param mixed $message
     * @param array $method
     * @return void
     * @throws
     */
    function error(mixed $message, array $method = []): void
    {
        if (is_string($message) && str_contains($message, 'inotify_rm_watch')) {
            return;
        }
        Kiri::getLogger()->failure($message);
    }
}


if (!function_exists('trigger_print_error')) {

    /**
     * @param mixed $message
     * @param string $method
     * @return bool
     * @throws
     */
    function trigger_print_error(mixed $message, string $method = 'app'): bool
    {
        return Kiri::getLogger()->failure($message, $method);
    }
}


if (!function_exists('event')) {


    /**
     * @param object $object
     * @return void
     * @throws ReflectionException
     */
    function event(object $object): void
    {
        Kiri::getDi()->get(EventDispatch::class)->dispatch($object);
    }

}


if (!function_exists('throwable')) {


    /**
     * 错误格式化
     * @param Throwable|Error|string $throwable
     * @return string
     */
    function throwable(\Throwable|\Error|string $throwable): string
    {
        if (is_string($throwable)) {
            return $throwable;
        }
        $message = "\033[31m" . $throwable::class . ' ' . $throwable->getMessage() . "\033[0m" . PHP_EOL;
        $message .= $throwable->getFile() . " at line " . $throwable->getLine() . PHP_EOL;

        $file = $throwable->getFile();
        $line = $throwable->getLine();

        foreach ($throwable->getTrace() as $value) {
            if (!isset($value['file'])) {
                $value['file'] = $file;
            }
            if (!isset($value['line'])) {
                $value['line'] = $line;
            }
            $file = $value['file'];
            $line = $value['line'];

            $message .= $value['file'] . ' -> ' . (isset($value['class']) ? $value['class'] . '::' : '') . ($value['function'] ?? 'Closure') . ' line ' . $line . PHP_EOL;
        }
        return $message;
    }

}


if (!function_exists('map')) {

    /**
     * @param array $map
     * @param Closure $closure
     * @return void
     */
    function map(array $map, Closure $closure): void
    {
        foreach ($map as $key => $value) {
            $closure($key, $value);
        }
    }
}
