<?php


namespace Annotation;


interface Porters
{


	#[Port(port: 999)]
	public function process();


}
