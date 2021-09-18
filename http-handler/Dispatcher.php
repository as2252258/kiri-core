<?php

namespace Http\Handler;

use Exception;
use Kiri\Core\Help;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 *
 */
class Dispatcher extends \Http\Handler\Abstracts\Handler
{


	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$response = $this->execute($request);
		if (!$response instanceof ResponseInterface) {
			return $this->transferToResponse($response);
		}
		return $response;
	}


	/**
	 * @param mixed $responseData
	 * @return \Server\Constrict\ResponseInterface
	 * @throws Exception
	 */
	private function transferToResponse(mixed $responseData): ResponseInterface
	{
		$interface = response()->withStatus(200);
		if (!$interface->hasContentType()) {
			$interface->withContentType('application/json;charset=utf-8');
		}
		if (is_object($responseData)) {
			$responseData = get_object_vars($responseData);
		}
		if ($interface->getContentType() == 'application/xml;charset=utf-8') {
			$interface->getBody()->write(Help::toXml($responseData));
		} else if (is_array($responseData)) {
			$interface->getBody()->write(json_encode($responseData));
		} else {
			$interface->getBody()->write((string)$responseData);
		}
		return $interface;
	}
}
