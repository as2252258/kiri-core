<?php


namespace Http\Constrict;


use Kiri\Context;
use Http\Message\ContentType;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Psr\Http\Message\StreamInterface;
use Http\Message\ServerRequest as RequestMessage;
use Http\Message\Response as Psr7Response;
use Server\ServerManager;
use Http\OnDownloadInterface;


/**
 * Class Response
 * @package Server
 */
class Response implements ResponseInterface
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';
	const FILE = 'file';


    /**
     * @throws ConfigException
     */
    public function __construct()
    {
        $contentType = Config::get('response',[
            'format'    =>  ContentType::JSON,
            'charset'   =>  'utf-8'
        ]);
        $this->withContentType($contentType['format'] ?? ContentType::JSON)
            ->withCharset($contentType['charset'] ?? 'utf-8');
    }


    /**
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return $this->__call__()->{$name};
	}


	/**
	 * @return Psr7Response
	 */
	public function __call__(): Psr7Response
	{
		return Context::getContext(ResponseInterface::class, new Psr7Response());
	}


	/**
	 * @return string
	 */
	public function getProtocolVersion(): string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $version
	 * @return ResponseInterface|Psr7Response
	 */
	public function withProtocolVersion($version): ResponseInterface|Psr7Response
	{
		return $this->__call__()->{__FUNCTION__}($version);
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeader($name): string
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeaderLine($name): string
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return ResponseInterface|Psr7Response
	 */
	public function withHeader($name, $value): ResponseInterface|Psr7Response
	{
		return $this->__call__()->{__FUNCTION__}($name, $value);
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return ResponseInterface|Psr7Response
	 */
	public function withAddedHeader($name, $value): ResponseInterface|Psr7Response
	{
		return $this->__call__()->{__FUNCTION__}($name, $value);
	}


	/**
	 * @param string $name
	 * @return ResponseInterface|Psr7Response
	 */
	public function withoutHeader($name): ResponseInterface|Psr7Response
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @return StreamInterface
	 */
	public function getBody(): StreamInterface
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param StreamInterface $body
	 * @return ResponseInterface|Psr7Response
	 */
	public function withBody(StreamInterface $body): ResponseInterface|Psr7Response
	{
		return $this->__call__()->{__FUNCTION__}($body);
	}

	/**
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return ResponseInterface|Psr7Response
	 */
	public function withStatus($code, $reasonPhrase = ''): ResponseInterface|Psr7Response
	{
		return $this->__call__()->{__FUNCTION__}($code, $reasonPhrase);
	}


	/**
	 * @return string
	 */
	public function getReasonPhrase(): string
	{
		return $this->__call__()->{__FUNCTION__}();
	}

	/**
	 * @param string $path
	 * @return OnDownloadInterface
	 */
	public function file(string $path): OnDownloadInterface
	{
		return $this->__call__()->{__FUNCTION__}($path);
	}

	/**
	 * @param $responseData
	 * @return string|array|bool|int|null
	 */
	public function _toArray($responseData): string|array|null|bool|int
	{
		return $this->__call__()->{__FUNCTION__}($responseData);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function xml($data): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($data);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function html($data): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($data);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function json($data): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($data);
	}

	/**
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->__call__()->{__FUNCTION__}();
	}

	/**
	 * @return bool
	 */
	public function hasContentType(): bool
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $type
	 * @return ResponseInterface
	 */
	public function withContentType(string $type): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($type);
	}


	/**
	 * @param string|null $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowOrigin(?string $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($value);
	}


	/**
	 * @param string|null $value
	 * @return ResponseInterface
	 */
	public function withAccessControlRequestMethod(?string $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($value);
	}


	/**
	 * @param string|null $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowHeaders(?string $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($value);
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowOrigin(): ?string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowHeaders(): ?string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlRequestMethod(): ?string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @return int
	 */
	public function getClientId(): int
	{
		if (!Context::hasContext('client.id.property')) {
			$request = Context::getContext(RequestInterface::class, new RequestMessage());
			return Context::setContext('client.id.property', $request->getClientId());
		}
		return (int)Context::getContext('client.id.property');
	}


	/**
	 * @return array
	 */
	public function getClientInfo(): array
	{
		if (!Context::hasContext('client.info.property')) {
			$request = Context::getContext(RequestInterface::class, new RequestMessage());

			$server = Kiri::getDi()->get(ServerManager::class)->getServer();

			$clientInfo = $server->getClientInfo($request->getClientId());

			return Context::setContext('client.info.property', $clientInfo);
		}
		return Context::getContext('client.info.property');
	}

}
