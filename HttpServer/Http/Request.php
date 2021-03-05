<?php
declare(strict_types=1);

namespace HttpServer\Http;

use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\IInterface\AuthIdentity;

use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Core\Json;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use function router;

defined('REQUEST_OK') or define('REQUEST_OK', 0);
defined('REQUEST_FAIL') or define('REQUEST_FAIL', 500);

/**
 * Class HttpRequest
 *
 * @package Snowflake\Snowflake\HttpRequest
 *
 * @property-read $isPost
 * @property-read $isGet
 * @property-read $isOption
 * @property-read $isDelete
 * @property-read $isHttp
 * @property-read $method
 * @property-read $identity
 * @property-read $isPackage
 * @property-read $isReceive
 */
class Request extends HttpService
{

	public int $fd = 0;

	public ?HttpParams $params;

	public ?HttpHeaders $headers;

	public bool $isCli = FALSE;

	public float $startTime;

	public string $uri = '';

	public int $statusCode = 200;

	/** @var string[] */
	private array $explode = [];

	const PLATFORM_MAC_OX = 'mac';
	const PLATFORM_IPHONE = 'iphone';
	const PLATFORM_ANDROID = 'android';
	const PLATFORM_WINDOWS = 'windows';


	/**
	 * @var AuthIdentity|null
	 */
	private ?AuthIdentity $_grant = null;


	/**
	 * @param $fd
	 */
	public function setFd($fd)
	{
		$this->fd = $fd;
	}


	/**
	 * @return array|null
	 * @throws ComponentException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function getConnectInfo(): array|null
	{
		if (empty($this->fd)) {
			return null;
		}
		$server = Snowflake::app()->getSwoole();

		return $server->getClientInfo($this->fd);
	}


	/**
	 * @return bool
	 */
	public function isFavicon(): bool
	{
		return $this->getUri() === 'favicon.ico';
	}

	/**
	 * @return mixed
	 */
	public function getIdentity(): mixed
	{
		return $this->_grant;
	}

	/**
	 * @return bool
	 */
	public function isHead(): bool
	{
		$result = $this->headers->getHeader('request_method') == 'head';
		if ($result) {
			$this->setStatus(101);
		} else {
			$this->setStatus(200);
		}
		return $result;
	}

	/**
	 * @param $status
	 * @return mixed
	 */
	public function setStatus($status): mixed
	{
		return $this->statusCode = $status;
	}

	/**
	 * @return int
	 */
	public function getStatus(): int
	{
		return $this->statusCode;
	}

	/**
	 * @return bool
	 */
	public function getIsPackage(): bool
	{
		return $this->headers->getHeader('request_method') == 'package';
	}

	/**
	 * @return bool
	 */
	public function getIsReceive(): bool
	{
		return $this->headers->getHeader('request_method') == 'receive';
	}


	/**
	 * @param $value
	 */
	public function setGrantAuthorization($value)
	{
		$this->_grant = $value;
	}


	/**
	 * @return bool
	 */
	public function hasGrant(): bool
	{
		return $this->_grant !== null;
	}


	/**
	 * @return string
	 */
	public function parseUri(): string
	{
		$array = [];
		$explode = explode('/', $this->headers->getHeader('request_uri'));
		foreach ($explode as $item) {
			if (empty($item)) {
				continue;
			}
			$array[] = $item;
		}
		return $this->uri = implode('/', ($this->explode = $array));
	}

	/**
	 * @return string[]
	 */
	public function getExplode(): array
	{
		return $this->explode;
	}

	/**
	 * @return string
	 */
	#[Pure] public function getCurrent(): string
	{
		return current($this->explode);
	}

	/**
	 * @return string
	 */
	public function getUri(): string
	{
		if (!$this->headers) {
			return 'command exec.';
		}
		if (!empty($this->uri)) {
			return $this->uri;
		}
		$uri = $this->headers->getHeader('request_uri');
		$uri = ltrim($uri, '/');
		if (empty($uri)) return '/';
		return $uri;
	}


	/**
	 * @return mixed
	 * @throws ComponentException
	 */
	public function adapter(): mixed
	{
		if (!$this->isHead()) {
			return router()->dispatch();
		}
		return '';
	}


	/**
	 * @return string|null
	 */
	public function getPlatform(): ?string
	{
		$user = $this->headers->getHeader('user-agent');
		$match = preg_match('/\(.*\)?/', $user, $output);
		if (!$match || count($output) < 1) {
			return null;
		}
		$output = strtolower(array_shift($output));
		if (strpos('mac', $output)) {
			return 'mac';
		} else if (strpos('iphone', $output)) {
			return 'iphone';
		} else if (strpos('android', $output)) {
			return 'android';
		} else if (strpos('windows', $output)) {
			return 'windows';
		}
		return null;
	}

	/**
	 * @return bool
	 */
	public function isIos(): bool
	{
		return $this->getPlatform() == static::PLATFORM_IPHONE;
	}

	/**
	 * @return bool
	 */
	public function isAndroid(): bool
	{
		return $this->getPlatform() == static::PLATFORM_ANDROID;
	}

	/**
	 * @return bool
	 */
	public function isMacOs(): bool
	{
		return $this->getPlatform() == static::PLATFORM_MAC_OX;
	}

	/**
	 * @return bool
	 */
	public function isWindows(): bool
	{
		return $this->getPlatform() == static::PLATFORM_WINDOWS;
	}

	/**
	 * @return bool
	 */
	public function getIsPost(): bool
	{
		return $this->getMethod() == 'post';
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function getIsHttp(): bool
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function getIsOption(): bool
	{
		return $this->getMethod() == 'options';
	}

	/**
	 * @return bool
	 */
	public function getIsGet(): bool
	{
		return $this->getMethod() == 'get';
	}

	/**
	 * @return bool
	 */
	public function getIsDelete(): bool
	{
		return $this->getMethod() == 'delete';
	}

	/**
	 * @return string
	 *
	 * 获取请求类型
	 */
	public function getMethod(): string
	{
		$method = $this->headers->get('request_method');
		if (empty($method)) {
			return 'get';
		}
		return strtolower($method);
	}

	/**
	 * @return bool
	 */
	public function getIsCli(): bool
	{
		return $this->isCli === TRUE;
	}


	/**
	 * @param $name
	 * @param $value
	 *
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		$method = 'set' . ucfirst($name);
		if (method_exists($this, $method)) {
			$this->$method($value);
		} else {
			parent::__set($name, $value); // TODO: Change the autogenerated stub
		}
	}

	/**
	 * @return mixed|null
	 */
	public function getIp(): string|null
	{
		$headers = $this->headers->getHeaders();
		if (!empty($headers['x-forwarded-for'])) return $headers['x-forwarded-for'];
		if (!empty($headers['request-ip'])) return $headers['request-ip'];
		if (!empty($headers['remote_addr'])) return $headers['remote_addr'];
		return NULL;
	}

	/**
	 * @return string
	 */
	public function getRuntime(): string
	{
		return sprintf('%.5f', microtime(TRUE) - $this->startTime);
	}

	/**
	 * @return string
	 */
	public function getDebug(): string
	{
		$mainstay = sprintf("%.6f", microtime(true)); // 带毫秒的时间戳

		$timestamp = floor($mainstay);                          // 时间戳
		$milliseconds = round(($mainstay - $timestamp) * 1000); // 毫秒

		$datetime = date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

		$tmp = [
			'[Debug ' . $datetime . '] ',
			$this->getIp(),
			$this->getUri(),
			'`' . $this->headers->getHeader('user-agent') . '`',
			$this->getRuntime()
		];

		return implode(' ', $tmp);
	}


	/**
	 * @param $router
	 * @return bool
	 */
	public function is($router): bool
	{
		return $this->getUri() == trim($router, '/');
	}

	/**
	 * @return bool
	 */
	public function isNotFound(): bool
	{
		return Json::to(404, 'Page ' . $this->getUri() . ' not found.');
	}


	/**
	 * @param $request
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public static function create($request): Request
	{
		/** @var Request $sRequest */
		$sRequest = Context::setContext('request', Snowflake::createObject(Request::class));
		$sRequest->fd = $request->fd;
		$sRequest->startTime = microtime(true);

		$sRequest->params = new HttpParams($request->rawContent(), $request->get, $request->files);
		if (!empty($request->post)) {
			$sRequest->params->setPosts($request->post ?? []);
		}
		$sRequest->headers = Snowflake::createObject(HttpHeaders::class, [merge($request->server, $request->header)]);
		$sRequest->uri = $sRequest->headers->get('request_uri');

		$sRequest->parseUri();
		return $sRequest;
	}


	/**
	 * @param $frame
	 * @param $route
	 * @param string $event
	 * @return Request
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public static function socketQuery($frame, $event = Socket::MESSAGE, $route = 'event'): Request
	{
		/** @var Request $sRequest */
		$sRequest = Snowflake::createObject(Request::class);
		$sRequest->fd = $frame->fd;
		$sRequest->startTime = microtime(true);

		$sRequest->params = new HttpParams([], [], []);
		$sRequest->headers = new HttpHeaders([]);
		$sRequest->headers->replace('request_method', 'sw::socket');
		$sRequest->headers->replace('request_uri', $event . '::' . $route);
		$sRequest->parseUri();

		return $sRequest;
	}


}
