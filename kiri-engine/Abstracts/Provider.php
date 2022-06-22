<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Kiri\Di\LocalService;

interface Provider
{

	public function onImport(LocalService $application);

}
