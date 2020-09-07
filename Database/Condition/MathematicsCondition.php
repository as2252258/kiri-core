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
		return $this->{strtolower($this->type)}((float)$this->value);
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function eq($value)
	{
		return $this->column . ' = ' . $value;
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function neq($value)
	{
		return $this->column . ' <> ' . $value;
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function gt($value)
	{
		return $this->column . ' > ' . $value;
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function egt($value)
	{
		return $this->column . ' >= ' . $value;
	}


	/**
	 * @param $value
	 * @return string
	 */
	public function lt($value)
	{
		return $this->column . ' < ' . $value;
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function elt($value)
	{
		return $this->column . ' <= ' . $value;
	}

}
