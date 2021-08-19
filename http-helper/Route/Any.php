<?php
declare(strict_types=1);


namespace Http\Route;


/**
 * Class Any
 * @package Kiri\Kiri\Route
 */
class Any
{

	private array $nodes = [];

	/**
	 * Any constructor.
	 * @param array $nodes
	 */
	public function __construct(array $nodes)
	{
		$this->nodes = $nodes;
	}


	/**
	 * @param $name
	 * @param $arguments
	 * @return $this
	 */
	public function __call($name, $arguments): static
	{
		foreach ($this->nodes as $node) {
			$node->{$name}(...$arguments);
		}
		return $this;
	}

}