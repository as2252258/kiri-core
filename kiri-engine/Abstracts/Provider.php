<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Kiri\Application;

interface Provider
{

	public function onImport(Application $application);

}
