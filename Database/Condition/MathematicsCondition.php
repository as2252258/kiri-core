<?php


namespace Database\Condition;

/**
 * Class MathematicsCondition
 * @package Database\Condition
 */
class MathematicsCondition extends Condition
{

	public $type = '';

	/**
	 * @return mixed
	 */
	public function builder()
	{
		$this->value = (float)$this->value;
		return $this->{strtolower($this->type)}();
	}

	/**
	 * @return string
	 */
	public function eq()
	{
		return $this->column . ' = ' . $this->value;
	}

	/**
	 * @return string
	 */
	public function neq()
	{
		return $this->column . ' <> ' . $this->value;
	}

	/**
	 * @return string
	 */
	public function gt()
	{
		return $this->column . ' > ' . $this->value;
	}

	/**
	 * @return string
	 */
	public function egt()
	{
		return $this->column . ' >= ' . $this->value;
	}


	/**
	 * @return string
	 */
	public function lt()
	{
		return $this->column . ' < ' . $this->value;
	}

	/**
	 * @return string
	 */
	public function elt()
	{
		return $this->column . ' <= ' . $this->value;
	}

}
