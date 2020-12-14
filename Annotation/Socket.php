<?php


namespace Annotation;


/**
 * Class Socket
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Socket
{

	const CLOSE = 'CLOSE';
	const MESSAGE = 'MESSAGE';
	const HANDSHAKE = 'HANDSHAKE';


	/**
	 * Socket constructor.
	 * @param string $event
	 * @param string|null $uri
	 */
	public function __construct(
		public string $event,
		public ?string $uri
	)
	{
	}

}
