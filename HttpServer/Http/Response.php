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
use JetBrains\PhpStorm\Pure;
use Snowflake\Core\Help;
use Snowflake\Snowflake;
use Swoole\Http\Response as SResponse;
use Swoole\Http2\Response as S2Response;

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

    public ?SResponse $response = null;
    public bool $isWebSocket = false;
    public array $headers = [];

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
        $this->format = $format;
        return $this;
    }

    /**
     * 清理无用数据
     */
    public function clear(): void
    {
        $this->fd = 0;
        $this->isWebSocket = false;
        $this->format = null;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        if ($this->format == null || $this->format == static::JSON) {
            return 'application/json;charset=utf-8';
        } else if ($this->format == static::XML) {
            return 'application/xml;charset=utf-8';
        } else {
            return 'text/html;charset=utf-8';
        }
    }


    /**
     * @param $content
     * @return mixed
     */
    public function toHtml($content): mixed
    {
        $this->format = self::HTML;
        return $content;
    }


    /**
     * @param $content
     * @return mixed
     */
    public function toJson($content): mixed
    {
        $this->format = self::JSON;
        return $content;
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
     * @return mixed
     * @throws Exception
     */
    public function sender(): mixed
    {
        return $this->send(func_get_args());
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
     * @return bool
     */
    private function isClient(): bool
    {
        return !($this->response instanceof SResponse) && !($this->response instanceof S2Response);
    }

    /**
     * @param string $context
     * @param int $statusCode
     * @param null $response
     * @return bool
     * @throws Exception
     */
    public function send($context = '', $statusCode = 200, $response = null): mixed
    {
        $sendData = $this->parseData($context);
        if ($response instanceof SResponse) {
            $this->response = $response;
        }
        if ($this->response instanceof SResponse) {
            $this->sendData($this->response, $sendData, $statusCode);
        } else {
            if (!empty(request()->fd)) {
                return '';
            }
            $this->printResult($sendData);
        }
        return $sendData;
    }

    /**
     * @param $context
     * @return mixed
     * @throws Exception
     */
    private function parseData($context): mixed
    {
        if ($context === null) {
            return '';
        }
        if (isset($this->_format_maps[$this->format])) {
            $className = $this->_format_maps[$this->format];
        } else {
            $className = HtmlFormatter::class;
        }
        return (new $className())->send($context)->getData();
    }

    /**
     * @param $result
     * @return string
     * @throws Exception
     */
    private function printResult($result): string
    {
        $result = Help::toString($result);
        $string = PHP_EOL . 'Command Result: ' . PHP_EOL . PHP_EOL;

        if (!str_contains($result, 'Event::rshutdown(): Event::wait()')) {
            if (empty($result)) {
                $string .= 'success!' . PHP_EOL . PHP_EOL;
            } else {
                $string .= $result . PHP_EOL . PHP_EOL;
            }
            $string .= 'Command End!' . PHP_EOL . PHP_EOL;
            print_r($string);
        }

        $event = Snowflake::app()->getEvent();
        $event->trigger('CONSOLE_END');

        return $result;
    }

    /**
     * @param $sendData
     * @param $status
     * @throws Exception
     */
    private function sendData($response, $sendData, $status): void
    {
        if (!swoole()->exist($response->fd) || !$response->isWritable()) {
            return;
        }
        $this->setHeaders($response, $status);
        if (empty($sendData)) {
            $response->end('');
        } else {
            $message = '[' . date('Y-m-d H:i:s') . ']' . $sendData . PHP_EOL . PHP_EOL;
            Snowflake::writeFile(storage('response.log'), $message, FILE_APPEND);
            $response->end($sendData);
        }
    }


    /**
     * @param $status
     */
    private function setHeaders($response, $status): void
    {
        $response->status($status);
        $response->header('Content-Type', $this->getContentType());
        $response->header('Run-Time', $this->getRuntime());

        if (empty($this->headers) || !is_array($this->headers)) {
            return;
        }
        foreach ($this->headers as $key => $header) {
            $response->header($key, $header, true);
        }
    }


    /**
     * @param $url
     * @param array $param
     * @return int
     */
    public function redirect($url, array $param = []): int
    {
        if (!empty($param)) {
            $url .= '?' . http_build_query($param);
        }
        $url = ltrim($url, '/');
        if (!preg_match('/^http/', $url)) {
            $url = '/' . $url;
        }
        return $this->response->redirect($url);
    }

    /**
     * @param null $response
     * @return mixed
     */
    public static function create($response = null): static
    {
        $ciResponse = new Response();
        $ciResponse->response = $response;
        $ciResponse->startTime = microtime(true);
        $ciResponse->format = self::JSON;
        Context::setContext('response', $ciResponse);
        return $ciResponse;
    }


    /**
     * @param int $statusCode
     * @param string $message
     * @return mixed
     * @throws Exception
     */
    public function close($statusCode = 200, $message = ''): mixed
    {
        return $this->send($message, $statusCode);
    }


    /**
     * @param $clientId
     * @param int $statusCode
     * @param string $message
     * @return mixed
     */
    public function closeClient($clientId, $statusCode = 200, $message = ''): mixed
    {
        $socket = Snowflake::getWebSocket();
        if (!$socket->exist($clientId)) {
            return true;
        }
        return $socket->close($clientId, true);
    }


    /**
     * @param string $path
     * @param int $offset
     * @param int $limit
     * @param int $sleep
     * @return string
     */
    public function sendFile(string $path, $offset = 0, $limit = 1024000, $sleep = 0): string
    {
        $open = fopen($path, 'r');

        $stat = fstat($open);

        while ($file = fread($open, $limit)) {
            $this->response->write($file);
            fseek($open, $offset);
            if ($sleep > 0) {
                sleep($sleep);
            }
            if ($offset >= $stat['size']) {
                break;
            }
            $offset += $limit;
        }
        $this->response->end();
        $this->response = null;
        return '';
    }


    /**
     * @throws Exception
     */
    public function sendNotFind()
    {
        $this->format = static::HTML;
        $this->send('', 404);
    }

    /**
     * @return string
     */
    #[Pure] public function getRuntime(): string
    {
        return sprintf('%.5f', microtime(TRUE) - $this->startTime);
    }

}
