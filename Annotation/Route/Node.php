<?php


namespace Annotation\Route;


trait Node
{


	/**
	 * @param $node
	 * @return mixed
	 */
	public function add($node): mixed
	{
		if (!empty($this->middleware)) {
			$node->addMiddleware($this->middleware);
		}
		if (!empty($this->interceptor)) {
			$node->addInterceptor($this->interceptor);
		}
		if (!empty($this->limits)) {
			$node->addLimits($this->limits);
		}
		if (!empty($this->after)) {
			$node->addAfter($this->after);
		}
		return $node;
	}

}
