<?php

use Snowflake\Process\Leafleting;
use Snowflake\Process\ServerInotify;

return [

	'processes' => [
		'Leafleting' => Leafleting::class,
		'inotify'    => ServerInotify::class,
	]

];
