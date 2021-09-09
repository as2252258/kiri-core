<?php

namespace Server;


use JetBrains\PhpStorm\Pure;
use Server\Message\Response;
use Server\SInterface\DownloadInterface;

/**
 * @mixin Response
 */
interface ResponseInterface extends \Psr\Http\Message\ResponseInterface
{


	/**
	 * @param string $path
	 * @return DownloadInterface
	 */
	public function file(string $path): DownloadInterface;


	/**
	 * @param $responseData
	 * @return string|array|bool|int|null
	 */
	public function _toArray($responseData): string|array|null|bool|int;


	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function xml($data): ResponseInterface;


	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function html($data): ResponseInterface;


	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function json($data): ResponseInterface;


	/**
	 * @return string
	 */
	public function getContentType(): string;


	/**
	 * @return bool
	 */
	public function hasContentType(): bool;


	/**
	 * @param string $type
	 * @return ResponseInterface
	 */
	public function withContentType(string $type): ResponseInterface;


	/**
	 * @param string $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowOrigin(string $value): ResponseInterface;


	/**
	 * @param string $value
	 * @return ResponseInterface
	 */
	public function withAccessControlRequestMethod(string $value): ResponseInterface;


	/**
	 * @param string $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowHeaders(string $value): ResponseInterface;



	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowOrigin(): ?string;



	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowHeaders(): ?string;


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlRequestMethod(): ?string;


}
