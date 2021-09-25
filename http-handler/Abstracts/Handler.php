<?php

namespace Http\Handler\Abstracts;

use Annotation\Inject;
use Http\Handler\Handler as CHl;
use Http\Message\ServerRequest;
use Kiri\Core\Help;
use Kiri\Events\EventDispatch;
use Kiri\Kiri;
use Kiri\Proxy\AspectProxy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Coroutine\Iterator;


abstract class Handler implements RequestHandlerInterface
{


    #[Inject(AspectProxy::class)]
	protected AspectProxy $aspectProxy;


    protected int $offset = 0;


    /**
     * @param \Http\Handler\Handler $handler
     * @param array|null $middlewares
     */
    public function __construct(public CHl $handler, public ?array $middlewares)
    {
        $this->aspectProxy = Kiri::getDi()->get(AspectProxy::class);
    }


    /**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws \Exception
	 */
	protected function execute(ServerRequestInterface $request): ResponseInterface
	{
		if (empty($this->middlewares) || !isset($this->middlewares[$this->offset])) {
			return $this->dispatcher($request);
		}

		$middleware = $this->middlewares[$this->offset];
		if (!($middleware instanceof MiddlewareInterface)) {
			throw new \Exception('get_implements_class($middleware) not found method process.');
		}

        $this->offset++;

		return $middleware->process($request, $this);
	}


	/**
	 * @param ServerRequestInterface $request
	 * @return mixed
	 * @throws \Exception
	 */
	public function dispatcher(ServerRequestInterface $request): mixed
	{
		$response = $this->aspectProxy->proxy($this->handler);
		if (!($response instanceof ResponseInterface)) {
			$response = $this->transferToResponse($response);
		}
		$response->withHeader('Run-Time', $this->_runTime($request));
		return $response;
	}


	/**
	 * @param ServerRequest $request
	 * @return float
	 */
	private function _runTime(ServerRequestInterface $request): float
	{
		$float = microtime(true) - time();

		$serverParams = $request->getServerParams();

		$rTime = $serverParams['request_time_float'] - $serverParams['request_time'];

		return round($float - $rTime, 6);
	}


	/**
	 * @param mixed $responseData
	 * @return \Server\Constrict\ResponseInterface
	 * @throws \Exception
	 */
	private function transferToResponse(mixed $responseData): ResponseInterface
	{
		$interface = response()->withStatus(200);
		if (!$interface->hasContentType()) {
			$interface->withContentType('application/json;charset=utf-8');
		}
		if (str_contains($interface->getContentType(), 'xml')) {
            if (is_object($responseData)) {
                $responseData = get_object_vars($responseData);
            }
			$interface->getBody()->write(Help::toXml($responseData));
		} else if (is_array($responseData)) {
			$interface->getBody()->write(json_encode($responseData));
		} else {
			$interface->getBody()->write((string)$responseData);
		}
		return $interface;
	}


}
