<?php


namespace Annotation\Route;


use Annotation\Attribute;

/**
 * Class Socket
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Socket extends Attribute
{

	const CLOSE = 'CLOSE';
	const MESSAGE = 'MESSAGE';
	const HANDSHAKE = 'HANDSHAKE';

	/**
	 * Socket constructor.
	 * @param string $event
	 * @param string|null $uri
	 * @param string $version
	 */
	public function __construct(string $event, ?string $uri = null, string $version = 'v.1.0')
	{
	}


}
