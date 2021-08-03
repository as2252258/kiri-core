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

    private mixed $endData;

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
        $this->format = $format;
        return $this;
    }


    /**
     * @param $content
     * @return string
     */
    public function toHtml($content): string
    {
        $this->format = self::HTML;
        var_dump($this->format);
        return (string)$content;
    }


    /**
     * @param $content
     * @return string|bool
     */
    public function toJson($content): string|bool
    {
        $this->format = self::JSON;
        return json_encode($content, JSON_UNESCAPED_UNICODE);
    }


    /**
     * @param $content
     * @return mixed
     */
    public function toXml($content): mixed
    {
        $this->format = self::XML;
        return $content;
    }


    /**
     * @param $key
     * @param $value
     * @return Response
     */
    public function addHeader($key, $value): static
    {
        $this->headers[$key] = $value;
        return $this;
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
        $this->cookies[] = func_get_args();
        return $this;
    }


    /**
     * @param $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }


    /**
     * @return string
     */
    public function getResponseFormat(): string
    {
        if ($this->format == self::HTML) {
            return 'text/html;charset=utf-8';
        } else if ($this->format == self::XML) {
            return 'application/xml;charset=utf-8';
        } else {
            return 'application/json;charset=utf-8';
        }
    }


    /**
     * @param mixed $context
     * @param int $statusCode
     * @return bool
     * @throws Exception
     */
    public function getBuilder(mixed $data, SResponse $response = null): static
    {
        if ($response != null) {
            $this->configure($response);
        }
        return $this->setContent($data);
    }


    /**
     * @param \Swoole\Http\Response|null $response
     * @throws \Exception
     */
    public function configure(SResponse $response = null): static
    {
        $response->setStatusCode($this->statusCode);
        var_dump($this->getResponseFormat());
        $response->header('Content-Type', $this->getResponseFormat());
        $response->header('Run-Time', $this->getRuntime());
        if (!empty($this->headers)) {
            foreach ($this->headers as $name => $header) {
                $response->header($name, $header);
            }
        }
        if (!empty($this->cookies)) {
            foreach ($this->cookies as $header) {
                $response->setCookie(...$header);
            }
        }
        return $this;
    }


    /**
     * @param $context
     * @return mixed
     * @throws Exception
     */
    private function parseData($context): mixed
    {
        if (!empty($context) && !is_string($context)) {
            /** @var IFormatter $class */
            $class = $this->_format_maps[$this->format] ?? HtmlFormatter::class;

            $di = Snowflake::getDi()->get($class);
            $context = $di->send($context)->getData();
        }
        return $context;
    }


    /**
     * @param mixed $content
     */
    public function setContent(mixed $content, $statusCode = 200, $format = self::JSON): static
    {
        $this->endData = $content;
        $this->setStatusCode($statusCode);
        $this->setFormat($format);
        return $this;
    }


    /**
     * @return string
     * @throws \ReflectionException
     * @throws \Snowflake\Exception\NotFindClassException
     */
    public function getContent(): string
    {
        if (empty($this->endData) || is_string($this->endData)) {
            return $this->endData;
        }

        /** @var IFormatter $class */
        $class = $this->_format_maps[$this->format] ?? HtmlFormatter::class;

        $di = Snowflake::getDi()->get($class);
        return $di->send($this->endData)->getData();
    }


    /**
     * @param $url
     * @param array $param
     * @return int
     */
    public function redirect($url, array $param = []): mixed
    {
        if (!empty($param)) {
            $url .= '?' . http_build_query($param);
        }
        $url = ltrim($url, '/');
        if (!preg_match('/^http/', $url)) {
            $url = '/' . $url;
        }
        /** @var SResponse $response */
        $response = Context::getContext('response');
        if (!empty($response)) {
            return $response->redirect($url);
        }
        return false;
    }


    /**
     * @param string $path
     * @param int $offset
     * @param int $limit
     * @param int $sleep
     * @return string
     */
    public function sendFile(string $path, int $offset = 0, int $limit = 1024000, int $sleep = 0): string
    {
        $open = fopen($path, 'r');

        $stat = fstat($open);


        /** @var SResponse $response */
        $response = Context::getContext('response');
        $response->header('Content-length', $stat['size']);
        while ($file = fread($open, $limit)) {
            $response->write($file);
            fseek($open, $offset);
            if ($sleep > 0) sleep($sleep);
            if ($offset >= $stat['size']) {
                break;
            }
            $offset += $limit;
        }
        $response->end();
        return '';
    }


    /**
     * @return string
     * @throws Exception
     */
    public function getRuntime(): string
    {
        return sprintf('%.5f', microtime(TRUE) - request()->getStartTime());
    }

}
