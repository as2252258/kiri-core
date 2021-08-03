<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:39
 */
declare(strict_types=1);

namespace HttpServer\Http;

use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Http\Formatter\HtmlFormatter;
use HttpServer\Http\Formatter\JsonFormatter;
use HttpServer\Http\Formatter\XmlFormatter;
use HttpServer\IInterface\IFormatter;
use Snowflake\Core\Help;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Http\Response as SResponse;

/**
 * Class Response
 * @package Snowflake\Snowflake\Http
 */
class Response extends HttpService
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';

	/** @var ?string */
	public ?string $format = null;

	/** @var int */
	public int $statusCode = 200;

	public array $headers = [];
	public array $cookies = [];

	private float $startTime = 0;

	private array $_format_maps = [
		self::JSON => JsonFormatter::class,
		self::XML  => XmlFormatter::class,
		self::HTML => HtmlFormatter::class
	];

	public int $fd = 0;

	/**
	 * @param $format
	 * @return $this
	 */
	public function setFormat($format): static
	{
        $this->__coroutine__call(__METHOD__, func_get_args());
        return $this;
	}


	/**
	 * @param $content
	 * @return string
	 */
	public function toHtml($content): string
	{
        $this->__coroutine__call(__METHOD__, func_get_args());
		return (string)$content;
	}


	/**
	 * @param $content
	 * @return string|bool
	 */
	public function toJson($content): string|bool
	{
        $this->__coroutine__call(__METHOD__, func_get_args());
		return json_encode($content, JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param $content
	 * @return mixed
	 */
	public function toXml($content): mixed
	{
        $this->__coroutine__call(__METHOD__, func_get_args());
		return $content;
	}


	/**
	 * @param $key
	 * @param $value
	 * @return Response
	 */
	public function addHeader($key, $value): static
	{
	    $this->__coroutine__call(__METHOD__, func_get_args());
		return $this;
	}


    /**
     * @param $name
     * @param $value
     */
	private function __coroutine__call($name, $value)
    {
        /** @var \HttpServer\Http\CoroutineResponse $handler */
        $handler = Context::getContext(CoroutineResponse::class);
        call_user_func([$handler, $name], ...$value);
    }


	/**
	 * @param $name
	 * @param null $value
	 * @param null $expires
	 * @param null $path
	 * @param null $domain
	 * @param null $secure
	 * @param null $httponly
	 * @param null $samesite
	 * @param null $priority
	 * @return Response
	 */
	public function addCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null): static
	{
        $this->__coroutine__call(__METHOD__, func_get_args());
		return $this;
	}


    /**
     * @param $statusCode
     */
	public function setStatusCode($statusCode)
    {
        $this->__coroutine__call(__METHOD__, func_get_args());
    }


	/**
	 * @param mixed $context
	 * @param int $statusCode
	 * @throws Exception
	 */
	public function send(mixed $context, SResponse $response): void
	{
	    $this->__coroutine__call(__METHOD__, func_get_args());
	}


	/**
	 * @param $url
	 * @param array $param
	 * @return int
	 */
	public function redirect($url, array $param = []): void
	{
        $this->__coroutine__call(__METHOD__, func_get_args());
    }


	/**
	 * @param string $path
	 * @param int $offset
	 * @param int $limit
	 * @param int $sleep
	 * @return string
	 */
	public function sendFile(string $path, int $offset = 0, int $limit = 1024000, int $sleep = 0): void
	{
        $this->__coroutine__call(__METHOD__, func_get_args());
    }

}
