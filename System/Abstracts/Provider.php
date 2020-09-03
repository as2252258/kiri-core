<?php


namespace Snowflake\Abstracts;


use Snowflake\Application;

interface Provider
{

	public function onImport(Application $application);

}
