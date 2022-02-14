<?php

namespace Kiri\Websocket;

use JetBrains\PhpStorm\Pure;
use Kiri\Annotation\Inject;
use Kiri\Core\HashMap;
use Swoole\Http\Response;

class FdCollector
{


	#[Inject(HashMap::class)]
	public HashMap $fds;


	/**
	 * @param int $fd
	 * @param Response $response
	 * @return void
	 */
	public function set(int $fd, Response $response)
	{
		$this->fds->put('fd_' . $fd, $response);
	}


	/**
	 * @param int $fd
	 * @return bool
	 */
	#[Pure] public function has(int $fd): bool
	{
		return $this->fds->has('fd_' . $fd);
	}

	/**
	 * @param int $fd
	 * @return ?Response
	 */
	#[Pure] public function get(int $fd): ?Response
	{
		return $this->fds->get('fd_' . $fd);
	}

	/**
	 * @param int $fd
	 * @return void
	 */
	public function remove(int $fd)
	{
		$this->fds->del('fd_' . $fd);
	}

}
