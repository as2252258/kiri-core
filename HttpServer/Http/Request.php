<?php
declare(strict_types=1);

namespace HttpServer\Http;

use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\IInterface\AuthIdentity;
use JetBrains\PhpStorm\Pure;
use Server\ServerManager;
use Snowflake\Core\Json;
use Snowflake\Snowflake;

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


	public ?HttpParams $params = null;


	public ?HttpHeaders $headers = null;

	public bool $isCli = FALSE;

	public float $startTime;

	public ?array $clientInfo;


	private string $_method = '';

	private string $_uri = '';

	public int $statusCode = 200;

	/** @var string[] */
	private array $explode = [];

	const PLATFORM_MAC_OX = 'mac';
	const PLATFORM_IPHONE = 'iphone';
	const PLATFORM_ANDROID = 'android';
	const PLATFORM_WINDOWS = 'windows';


	const HTTP_POST = 'post';
	const HTTP_GET = 'GET';
	const HTTP_CMD = 'rpc';
	const HTTP_LISTEN = 'listen';
	const HTTP_SOCKET = 'sw::socket';



	private string $_platform = '';


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
	 * @throws Exception
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
	 * @return int
	 */
	public function getClientId(): int
	{
		return $this->fd;
	}


	/**
	 * @return bool
	 */
	#[Pure] public function isFavicon(): bool
	{
		return $this->_uri === 'favicon.ico';
	}

	/**
	 * @return AuthIdentity|null
	 */
	public function getIdentity(): ?AuthIdentity
	{
		return $this->_grant;
	}

	/**
	 * @return bool
	 */
	public function isHead(): bool
	{
		$result = $this->_method == 'HEAD';
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
		return $this->_method == 'package';
	}

	/**
	 * @return bool
	 */
	public function getIsReceive(): bool
	{
		return $this->_method == 'receive';
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
		return $this->_uri;
	}

	/**
	 * @return string|null
	 */
	public function getPlatform(): ?string
	{
		if (!empty($this->_platform)) {
			return $this->_platform;
		}
		$user = $this->headers->getHeader('user-agent');
		$match = preg_match('/\(.*\)?/', $user, $output);
		if (!$match || count($output) < 1) {
			return $this->_platform = 'unknown';
		}
		$output = strtolower(array_shift($output));
		if (strpos('mac', $output)) {
			return $this->_platform = 'mac';
		} else if (strpos('iphone', $output)) {
			return $this->_platform = 'iphone';
		} else if (strpos('android', $output)) {
			return $this->_platform = 'android';
		} else if (strpos('windows', $output)) {
			return $this->_platform = 'windows';
		} else {
			return $this->_platform = 'unknown';
		}
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
		return $this->_method == 'POST';
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
		return $this->_method == 'OPTIONS';
	}

	/**
	 * @return bool
	 */
	public function getIsGet(): bool
	{
		return $this->_method == 'GET';
	}

	/**
	 * @return bool
	 */
	public function getIsDelete(): bool
	{
		return $this->_method == 'DELETE';
	}

	/**
	 * @return string
	 *
	 * 获取请求类型
	 */
	public function getMethod(): string
	{
		$method = $this->_method;
		if (empty($method)) {
			return 'GET';
		}
		return $method;
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
	#[Pure] public function getIp(): string|null
	{
		$headers = $this->headers->getHeaders();
		if (!empty($headers['remoteip'])) return $headers['remoteip'];
		if (!empty($headers['x-forwarded-for'])) return $headers['x-forwarded-for'];
		if (!empty($headers['request-ip'])) return $headers['request-ip'];
		if (!empty($headers['remote_addr'])) return $headers['remote_addr'];
		return NULL;
	}

	/**
	 * @return string
	 */
	#[Pure] public function getRuntime(): string
	{
		return sprintf('%.5f', microtime(TRUE) - $this->startTime);
	}

	/**
	 * @return string
	 */
	public function getDebug(): string
	{
		$mainstay = sprintf("%.6f", microtime(true)); // 带毫秒的时间戳

		$timestamp = floatval($mainstay);                          // 时间戳
		$milliseconds = round(($mainstay - $timestamp) * 1000);    // 毫秒

		$datetime = date("Y-m-d H:i:s", (int)$timestamp) . '.' . $milliseconds;

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
	#[Pure] public function is($router): bool
	{
		return $this->_uri == $router;
	}

	/**
	 * @return bool
	 */
	public function isNotFound(): bool
	{
		return Json::to(404, 'Page ' . $this->_uri . ' not found.');
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return Request
	 */
	public static function create(\Swoole\Http\Request $request): Request
	{
		$httpRequest = new Request();
		$httpRequest->fd = $request->fd;

		$server = ServerManager::getContext()->getServer();
		$httpRequest->clientInfo = $server->getClientInfo($request->fd);

		$httpRequest->headers = new HttpHeaders();
		$httpRequest->headers->addHeaders(array_merge($request->header, $request->server));

		$httpRequest->_uri = $httpRequest->headers->get('request_uri');
		$httpRequest->_method = $httpRequest->headers->get('request_method');

		$httpRequest->params = new HttpParams();
		$httpRequest->params->setPosts($request->post);
		$httpRequest->params->setFiles($request->files);
		$httpRequest->params->setGets($request->get);
		$httpRequest->params->setRawContent($request->rawContent(), $httpRequest);

		return Context::setContext('request', $httpRequest);
	}


}
