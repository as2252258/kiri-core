<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/16 0016
 * Time: 10:24
 */
declare(strict_types=1);

namespace validator;


class UniqueValidator extends BaseValidator
{

	/**
	 * @return bool
	 * @throws
	 * 检查是否存在
	 */
	public function trigger(): bool
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return TRUE;
		}

		if (empty($this->model)) {
			return $this->addError('Model error.');
		}

		$model = $this->model;
		if (!$this->model->getIsCreate()) {
			return true;
		}
		if ($model::find()->where([$this->field => $param[$this->field]])->exists()) {
			return $this->addError('The :attribute \'' . $param[$this->field] . '\' is exists!');
		}
		return $this->isFail = TRUE;
	}


}
