<?php

namespace Server\Abstracts\Utility;

use Annotation\Inject;
use Kiri\Events\EventDispatch;

trait EventDispatchHelper
{

	/** @var EventDispatch */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


}