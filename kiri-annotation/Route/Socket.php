<?php


namespace Kiri\Annotation\Route;


use Kiri\Annotation\AbstractAttribute;

/**
 * Class Socket
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Socket extends AbstractAttribute
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
