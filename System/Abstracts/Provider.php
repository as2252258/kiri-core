<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Snowflake\Application;

interface Provider
{

	public function onImport(Application $application);

}
