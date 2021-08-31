<?php

namespace Server\Message;

use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri\Core\Help;
use Kiri\ToArray;
use Psr\Http\Message\ResponseInterface;


/**
 *
 */
class Response implements ResponseInterface, \Server\ResponseInterface
{
	use Message;

	public int $statusCode = 200;


	public string $reasonPhrase = '';

	const CONTENT_TYPE_JSON = 'application/json;charset=utf-8';
	const CONTENT_TYPE_HTML = 'text/html;charset=utf-8';
	const CONTENT_TYPE_STREAM = 'octet-stream';
	const CONTENT_TYPE_XML = 'application/xml;charset=utf-8';


	/**
	 *
	 */
	#[Pure] public function __construct()
	{
		$this->stream = new Stream('');
	}


	/**
	 * @param string $type
	 * @return $this
	 * @throws Exception
	 */
	public function withContentType(string $type): static
	{
		if (!in_array($type, [
			Response::CONTENT_TYPE_HTML, Response::CONTENT_TYPE_JSON,
			Response::CONTENT_TYPE_STREAM, Response::CONTENT_TYPE_XML
		])) {
			throw new Exception('Wrong content type.');
		}
		return $this->withHeader('Content-Type', $type);
	}


	/**
	 * @return int
	 */
	public function getStatusCode(): int
	{
		// TODO: Implement getStatusCode() method.
		return $this->statusCode;
	}

	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return static
	 */
	public function withStatus($code, $reasonPhrase = ''): static
	{
		$this->statusCode = $code;
		$this->reasonPhrase = $reasonPhrase;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getReasonPhrase(): string
	{
		// TODO: Implement getReasonPhrase() method.
		return $this->reasonPhrase;
	}


	/**
	 * @param $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowHeaders($value): ResponseInterface
	{
		return $this->withHeader('Access-Control-Allow-Headers', $value);
	}


	/**
	 * @param $value
	 * @return ResponseInterface
	 */
	public function withAccessControlRequestMethod($value): ResponseInterface
	{
		return $this->withHeader('Access-Control-Request-Method', $value);
	}


	/**
	 * @param $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowOrigin($value): ResponseInterface
	{
		return $this->withHeader('Access-Control-Allow-Origin', $value);
	}


	/**
	 * @param $data
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function json($data): ResponseInterface
	{
		if (!is_array($data = $this->_toArray($data))) {
			throw new Exception('Json data format error.');
		}

		$this->stream->write(json_encode($this->_toArray($data)));

		return $this->withContentType(self::CONTENT_TYPE_JSON);
	}


	/**
	 * @param $data
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function html($data): ResponseInterface
	{
		$this->stream->write((string)$this->_toArray($data));

		return $this->withContentType(self::CONTENT_TYPE_HTML);
	}


	/**
	 * @param $data
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function xml($data): ResponseInterface
	{
		if (!is_array($data = $this->_toArray($data))) {
			throw new Exception('Xml data format error.');
		}

		$this->stream->write(Help::toXml($data));

		return $this->withContentType(self::CONTENT_TYPE_XML);
	}


	/**
	 * @param $responseData
	 * @return string|array|bool|int|null
	 */
	public function _toArray($responseData): string|array|null|bool|int
	{
		if (is_object($responseData)) {
			$responseData = $responseData instanceof ToArray ? $responseData->toArray() : get_object_vars($responseData);
		}
		return $responseData;
	}

}
