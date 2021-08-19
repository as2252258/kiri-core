<?php
declare(strict_types=1);

namespace Http\Context;

use Annotation\Inject;
use Exception;
use Http\Abstracts\HttpService;
use Http\IInterface\AuthIdentity;
use JetBrains\PhpStorm\Pure;
use Server\RequestInterface;
use Server\ServerManager;
use Kiri\Core\Json;

defined('REQUEST_OK') or define('REQUEST_OK', 0);
defined('REQUEST_FAIL') or define('REQUEST_FAIL', 500);

/**
 * Class HttpRequest
 *
 * @package Kiri\Kiri\HttpRequest
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
class Request extends HttpService implements RequestInterface
{


    use HttpHeaders, HttpParams;

    private array $_explode = [];
    private string $_uri = '';
	private int $fd = 0;

    private bool $isCli = FALSE;

    private float $startTime;

    private int $statusCode = 200;

	private int $_clientId = 0;

	/** @var string[] */
	private array $explode = [];

	const PLATFORM_MAC_OX = 'mac';
	const PLATFORM_IPHONE = 'iphone';
	const PLATFORM_ANDROID = 'android';
	const PLATFORM_WINDOWS = 'windows';


	private string $_platform = '';


	/**
	 * @var AuthIdentity|null
	 */
	private ?AuthIdentity $_grant = null;


	/**
	 * @param $id
	 */
	public function setClientId($id)
	{
		$this->_clientId = $id;
	}


	/**
	 * @param string $request_uri
	 */
	public function setUri(string $request_uri)
	{
		$request_uri = array_filter(explode('/', $request_uri));

		$this->_explode = $request_uri;
		$this->_uri = '/' . implode('/', $request_uri);
	}

	/**
	 * @return array|null
	 * @throws Exception
	 */
	public function getConnectInfo(): array|null
	{
		$server = di(ServerManager::class)->getServer();

		return $server->getClientInfo($this->getClientId());
	}


	/**
	 * @return mixed
	 */
	public function getStartTime(): mixed
	{
		return $this->getHeader('request_time_float');
	}


	/**
	 * @return int
	 */
	public function getClientId(): int
	{
		return $this->_clientId ?? 0;
	}


	/**
	 * @return bool
	 */
	public function isFavicon(): bool
	{
		return $this->getRequestUri() === 'favicon.ico';
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
		$result = $this->getRequestMethod() == 'HEAD';
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
		return $this->getRequestMethod() == 'package';
	}

	/**
	 * @return bool
	 */
	public function getIsReceive(): bool
	{
		return $this->getRequestMethod() == 'receive';
	}


	/**
	 * @param $value
	 */
	public function setGrantAuthorization($value)
	{
		return $this->_grant = $value;
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
		return $this->_explode;
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

		$user = $this->getAgent();
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
		return $this->getRequestMethod() == 'POST';
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
		return $this->getRequestMethod() == 'OPTIONS';
	}

	/**
	 * @return bool
	 */
	public function getIsGet(): bool
	{
		return $this->getRequestMethod() == 'GET';
	}

	/**
	 * @return bool
	 */
	public function getIsDelete(): bool
	{
		return $this->getRequestMethod() == 'DELETE';
	}

	/**
	 * @return string
	 *
	 * 获取请求类型
	 */
	public function getMethod(): string
	{
		$method = $this->getRequestMethod();
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
		$headers = $this->getHeaders();
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
			'`' . $this->getAgent() . '`',
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
		return $this->getRequestUri() == $router;
	}

	/**
	 * @return bool
	 */
	public function isNotFound(): bool
	{
		return Json::to(404, 'Page ' . $this->getRequestUri() . ' not found.');
	}




}