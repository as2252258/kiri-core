<?php

namespace Http;

use Swoole\Http\Response;

interface OnDownloadInterface
{

	public function dispatch(Response $response);

}
