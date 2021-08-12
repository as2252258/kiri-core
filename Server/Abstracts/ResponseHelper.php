<?php

namespace Server\Abstracts;

use Annotation\Inject;
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


	/** @var ResponseEmitter  */
	#[Inject(ResponseEmitter::class)]
	public ResponseEmitter $responseEmitter;


}
