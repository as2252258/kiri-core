<?php
declare(strict_types=1);


namespace Queue;


interface Consumer
{


	public function __construct(array $params);



	public function onWaiting();


	public function onRunning();


	public function onComplete();


}
