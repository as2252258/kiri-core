<?php
declare(strict_types=1);

namespace Snowflake\Jwt;

use Annotation\Inject;
use Exception;
use Server\Constrict\Request;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Exception\ConfigException;


/**
 * Class Jwt
 * @package Snowflake\Jwt
 */
class Jwt extends Component
{

	use JwtHelper;


	private Request $request;


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function init()
	{
		$this->request = di(Request::class);
		if (!Config::has('ssl.public') || !Config::has('ssl.private')) {
			return;
		}
		$this->public = Config::get('ssl.public', $this->public);
		$this->private = Config::get('ssl.private', $this->private);
		$this->timeout = Config::get('ssl.timeout', 7200);
	}

	/**
	 * @param int $unionId
	 *
	 * @return string
	 * @throws Exception
	 */
	public function create(int $unionId): string
	{
		$this->user = $unionId;
		$this->config['time'] = time();
		$this->data = $this->request->getHeaders();
		if (!isset($this->data['source'])) {
			$this->data['source'] = 'browser';
		}
		return $this->createEncrypt($unionId);
	}


	/**
	 * @return string
	 */
	private function jwtHeader(): string
	{
		return base64_encode(json_encode(['type' => 'openssl', 'encrypt' => $this->encrypt]));
	}


	/**
	 * @param $unionId
	 * @return string
	 * @throws Exception
	 */
	private function jwtBody($unionId): string
	{
		$json = json_encode(['unionId' => $unionId, 'createTime' => time(), 'loginIp' => request()->getIp(), 'expire_at' => time() + $this->timeout]);
		openssl_private_encrypt($json, $encode, $this->private);
		return base64_encode($encode);
	}


	/**
	 * @param $unionId
	 * @return string
	 * @throws Exception
	 */
	private function createEncrypt($unionId): string
	{
		$params[] = $this->jwtHeader();
		$params[] = $this->jwtBody($unionId);

		$params[] = hash($this->encrypt, $params[0] . $params[1]);
		return implode('.', $params);
	}


	/**
	 * @param $token
	 * @return string
	 * @throws JWTAuthTokenException
	 */
	public function getUnionId($token): string
	{
		return $this->unpack($token)['unionId'];
	}


	/**
	 * @param $token
	 * @return bool
	 * @throws JWTAuthTokenException
	 */
	public function validator($token): bool
	{
		$unpack = $this->unpack($token);
		if ($unpack['expire_at'] < time()) {
			return false;
		}
		return true;
	}


	/**
	 * @param $token
	 * @return string
	 * @throws JWTAuthTokenException
	 * @throws Exception
	 */
	public function refresh($token): string
	{
		return $this->create($this->unpack($token)['unionId']);
	}


	/**
	 * @param string $token
	 * @return mixed
	 * @throws JWTAuthTokenException
	 */
	private function unpack(string $token): string
	{
		if (count($explode = explode('.', $token)) != 3) {
			throw new JWTAuthTokenException('JWT Voucher Format Error.');
		}
		if (hash($this->encrypt, $explode[0] . $explode[1]) != $explode[2]) {
			throw new JWTAuthTokenException('JWT Sign Validator Fail.');
		}
		if (!openssl_public_decrypt(base64_decode($explode[1]), $decode, $this->public)) {
			throw new JWTAuthTokenException('JWT Voucher Unpack Error.');
		}
		return Json::decode($decode, true);
	}

}
