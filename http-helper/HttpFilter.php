<?php


namespace Http;


use Exception;
use Kiri\Abstracts\BaseObject;
use validator\Validator;

/**
 * Class HttpFilter
 * @package Http\Http
 */
class HttpFilter extends BaseObject
{


    private array $hash = [];


    /**
     * @param string $className
     * @param string $method
     * @param array $rules
     * @return $this
     */
    public function register(string $className, string $method, array $rules): HttpFilter
    {
        $this->hash[$className . '::' . $method] = $rules;
        return $this;
    }


    /**
     * @param string $className
     * @param string $method
     * @return array
     */
    public function getRules(string $className, string $method): array
    {
        return $this->hash[$className . '::' . $method] ?? [];
    }


	/**
	 * @param array $rules
	 * @return bool|Validator
	 * @throws Exception
	 */
    public function check(array $rules): bool|Validator
    {
        if (empty($rules)) {
            return true;
        }
        $validator = Validator::getInstance();
        $validator->setParams(Input()->load());
        foreach ($rules as $val) {
            $field = array_shift($val);
            if (empty($val)) {
                continue;
            }
            $validator->make($field, $val);
        }
        return $validator;
    }

}
