<?php


//ini_set('memory_limit','3096M');

use Snowflake\Application;

require_once __DIR__ . '/vendor/autoload.php';
$config = array_merge(
	require_once __DIR__ . '/System/Process/config.php',
	require_once __DIR__ . '/HttpServer/config.php'
);

$application = new Application($config);
$application->start();
