<?php

namespace Server\SInterface;

use Swoole\Http\Response;

interface DownloadInterface
{

	public function dispatch(Response $response);

}
