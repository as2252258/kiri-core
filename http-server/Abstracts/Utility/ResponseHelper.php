<?php

namespace Server\Abstracts\Utility;

use Annotation\Inject;
use Server\Constrict\Emitter;
use Server\Constrict\Response as CResponse;
use Server\Constrict\ResponseEmitter;


/**
 *
 */
trait ResponseHelper
{

	/** @var CResponse|mixed */
	#[Inject(CResponse::class)]
	public CResponse $response;


	public Emitter $responseEmitter;


}
