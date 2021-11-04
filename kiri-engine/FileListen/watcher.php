<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
putenv('SCAN_CACHEABLE=(true)');

$cwd = getcwd();

$dir = __DIR__ . '/../../../../../';

require_once $dir . 'kiri.php';
