<?php

namespace Server\Constrict;

use Annotation\Inject;
use Server\SInterface\DownloadInterface;
use Swoole\Server;


/**
 *
 */
class ResponseEmitter implements Emitter
{


	/**
	 * @var RequestInterface
	 */
	#[Inject(RequestInterface::class)]
	public RequestInterface $request;


	/**
	 * @param mixed $response
	 * @param \Server\Message\Response|ResponseInterface $emitter
	 */
	public function sender(mixed $response, ResponseInterface|\Server\Message\Response $emitter): void
	{
		if (is_array($emitter->getHeaders())) {
			foreach ($emitter->getHeaders() as $name => $values) {
				$response->header($name, implode(';', $values));
			}
		}
		if (is_array($emitter->getCookies())) {
			foreach ($emitter->getCookies() as $name => $cookie) {
				$response->cookie($name, ...$cookie);
			}
		}
		$response->setStatusCode($emitter->getStatusCode());
		$response->header('Server', 'swoole');
		$response->header('Swoole-Version', swoole_version());

		if (!($emitter instanceof DownloadInterface)) {
			$response->end($emitter->getBody());
		} else {
			$emitter->dispatch($response);
		}
	}

}
