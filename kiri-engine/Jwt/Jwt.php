<?php
declare(strict_types=1);

namespace Kiri\Jwt;

use Annotation\Inject;
use Exception;
use Http\Constrict\RequestInterface;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Cache\Redis;
use Kiri\Core\Json;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;


/**
 * Class Jwt
 * @package Kiri\Jwt
 */
class Jwt extends Component
{

	use JwtHelper;


	#[Inject(RequestInterface::class)]
	private RequestInterface $request;


	/**
	 * @param RequestInterface $request
	 */
	public function setRequest(RequestInterface $request): void
	{
		$this->request = $request;
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 * 'jwt' => [
	 * 'scene' => 'application',
	 * 'timeout' => 7200,
	 * 'encrypt' => '',
	 * 'iv'      => '',
	 * 'key'     => '',
	 * ]
	 */
	public function init()
	{
		$this->request = di(RequestInterface::class);

		$this->public = Config::get('ssl.public', $this->public);
		$this->private = Config::get('ssl.private', $this->private);
		$this->timeout = Config::get('jwt.timeout', 7200);

		$jwt = Config::get('jwt', []);
		if ($jwt) {
			$this->setScene($jwt['scene'] ?? 'application');
			$this->setKey($jwt['key'] ?? get_called_class());
			$length = openssl_cipher_iv_length($this->encrypt);
			if ($length > 0) {
				$defaultIv = openssl_random_pseudo_bytes($length);
				$this->setIv($jwt['iv'] ?? $defaultIv);
			}
		}
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
		$token = $this->createEncrypt($unionId);
		if ($this->oos) {
			$redis = Kiri::getDi()->get(Redis::class);
			$redis->set('_jwt:token:' . $unionId, $token);
			$redis->expire('_jwt:token:' . $unionId, $this->timeout);
		}
		return $token;
	}


	/**
	 * @return string
	 */
	private function jwtHeader(): string
	{
		return openssl_encrypt(
			json_encode(['type' => 'openssl', 'encrypt' => $this->encrypt], JSON_UNESCAPED_UNICODE),
			$this->encrypt,
			$this->key,
			0,
			$this->iv
		);
	}


	/**
	 * @param $unionId
	 * @return string
	 * @throws Exception
	 */
	private function jwtBody($unionId): string
	{
		$json = json_encode(['unionId' => $unionId, 'createTime' => time(), 'expire_at' => time() + $this->timeout], JSON_UNESCAPED_UNICODE);
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

		$params[] = hash('sha256', $params[0] . $params[1]);
		return implode('.', $params);
	}


	/**
	 * @param $token
	 * @return string|int
	 * @throws JWTAuthTokenException
	 */
	public function getUnionId($token): string|int
	{
		$unpack = $this->unpack($token);
		if (!$this->_validator($unpack)) {
			throw new JWTAuthTokenException('JWT certificate has expired.');
		}
		return $unpack['unionId'];
	}


	/**
	 * @param $token
	 * @return bool
	 * @throws JWTAuthTokenException
	 */
	public function validator($token): bool
	{
		return $this->_validator($this->unpack($token));
	}


	/**
	 * @param $unpack
	 * @return bool
	 */
	private function _validator($unpack): bool
	{
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
	private function unpack(string $token): array
	{
		if (count($explode = explode('.', $token)) != 3) {
			throw new JWTAuthTokenException('JWT Voucher Format Error.');
		}
		if (hash('sha256', $explode[0] . $explode[1]) != $explode[2]) {
			throw new JWTAuthTokenException('JWT Sign Validator Fail.');
		}
		if (!openssl_public_decrypt(base64_decode($explode[1]), $decode, $this->public)) {
			throw new JWTAuthTokenException('JWT Voucher Unpack Error.');
		}
		return Json::decode($decode, true);
	}

}
