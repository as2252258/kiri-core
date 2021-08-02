<?php

namespace Snowflake\Events;


/**
 *
 */
interface EventDispatchInterface
{

	public function getZOrder(): int;

	public function onHandler(): void;

	public function stopPagination(): bool;

}
