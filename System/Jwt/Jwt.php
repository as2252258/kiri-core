<?php
declare(strict_types=1);

namespace Snowflake\Jwt;

use Annotation\Aspect;
use Database\InjectProperty;
use Exception;
use HttpServer\Http\HttpHeaders;
use ReflectionException;
use Snowflake\Cache\Redis;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Str;
use Snowflake\Exception\AuthException;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * Class Jwt
 * @package Snowflake\Jwt
 */
class Jwt extends Component
{

    /** @var int $user */
    private int $user;

    private array $data;

    private array $source = ['browser', 'android', 'iphone', 'pc', 'mingame'];

    private array $config = ['token' => ''];

    private ?int $timeout = 7200;

    private string $key = 'www.xshucai.com';

    private ?string $public = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6BuML3gtLGde7QKNuNST
UCB9gdHC7XIpOc7Wx2I64Esj3UxWHTgp3URj0ge8zpy7A3FfBdppR7d1nwoD6Xad
jqfjEWpTy4WwGYsOfH0tFl3wAmse0lebF4NFsS9pzrikQT6c9qsVm88pCjvg4i5t
WhTMEnpTFDYoDR0KXlLXltQMudBBUHFaVwP0wKJ/cGX7R1Mrv35K4MXwQFOuGZkP
hsp2rO9x5LjtSKIXbexy7WhUu6QMjD/XzgsXr9UF+ExYmBGXRVWgNFLMkiaCZ2Uz
WlQhpQrA5/wKd76dCzjvqw9M32OiZl2lCKT73cV8GUvt7BNsM1SiPhqfY7nhO6y3
cwIDAQAB
-----END PUBLIC KEY-----';

    private ?string $private = '-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEA6BuML3gtLGde7QKNuNSTUCB9gdHC7XIpOc7Wx2I64Esj3UxW
HTgp3URj0ge8zpy7A3FfBdppR7d1nwoD6XadjqfjEWpTy4WwGYsOfH0tFl3wAmse
0lebF4NFsS9pzrikQT6c9qsVm88pCjvg4i5tWhTMEnpTFDYoDR0KXlLXltQMudBB
UHFaVwP0wKJ/cGX7R1Mrv35K4MXwQFOuGZkPhsp2rO9x5LjtSKIXbexy7WhUu6QM
jD/XzgsXr9UF+ExYmBGXRVWgNFLMkiaCZ2UzWlQhpQrA5/wKd76dCzjvqw9M32Oi
Zl2lCKT73cV8GUvt7BNsM1SiPhqfY7nhO6y3cwIDAQABAoIBADPihJHP8XktmmCs
43Vfv5Z3zNaKR2LA1Eph3E0xviuJYHkFqXJarbESqqW2qRQeoQeB/lXWnxYzAo4M
tRcpNss+6FlqRVUHi3gKR7C4Yq3PTemcfIVUpAy7gYa8LJDTYZRcJMZXNDtiMbBh
9kFZU4SBhaTTx2KLQKS9yyWOqzbBvyLXN+1+Wy477M9+MXXTKw79dO+pML6cR0yl
pNfVR5FX5L/GB5vOtQB/Aqg/CKT8NC5MzWPnKY+TPCCHZyoZuB9dLDuWOlqsN4QX
Y4B8fFca5yRwzHra5aGoqdaT/zGctt+I6V/f/KNQCo36f9LPxeXg1+FHvvtTj5WZ
N8CGPzECgYEA9R7lRMXzrHE4rK0DhxQXIFbIKKtxrimqZQdbwOUeYYD2R6CDSItK
z88RSYElmd6wiS7fYIaheXNqJ8Yu6SQFBF/yshBwjQVl9NJG94LJlgx1XnVZEju6
OZjMUOhHXBymtXnLo16pDRl8odc4MFLRH25/vLtwChUr+Qoyt54GzFUCgYEA8mjL
jdh94JAmcdnDXsKgjNOGyNWGDVvWoFmy8lEQsMXY1JJnEd3YfDM2prmv3vaoiXzi
YkSETl6ZUtJqh78MnHCBY1vI6EAcKQAF/kvP2TataRCXNcGNQwn2mtq+B+heTta6
Di8jjAdmdUAYHbmOQryBudiRYG7JEF038elzvKcCgYEAq81ByFguGBkrLev94vkz
1Fi+5bJ0dSuC4Fit+J8eEhz/gOiB26C1iL2LUkeQgS5R8XTG37K9DpDUQJhpXMMA
OTa+tgtLt6um8FdJokUq4V5ODSyWh28RcTklSzfifC8gsWVyU0kPl7zbW9uq6EPD
ixI5uaBuQMLiFSUOsx+xiBkCgYEAtqXHWeVZUy7KCNavomK7XeCzmfdovgAIw2FS
t8nk7YzlR6XYC1pAl7Ru5Ujb/v+TFaUHXkuJ9RLKK+Fna0jEU8thcl/iDTzg+vON
kIHG5j+Qga2CgXqI2Y5URXGz5XlsNbMNFUrnWcbpqEbW5O6/BgHLLSDEyQgwbygN
0zS3g9kCgYEAhssb7kOljdIul4lY5MXc67Zf1dp6S2bucLOxsG6cRW07b3pBz7QF
5aPE7ZwnkzTnA4HuGGauKj+qKGAR7ve55XClAq/XipiVFrjwV/t3LC6j5DoqTJYR
mlAZUEjsoaT9vjvjGTxl3uCm0TX5KTgtSJIt2kA1tYVjQef+/iZTHxY=
-----END RSA PRIVATE KEY-----';


    /**
     * @throws ConfigException
     */
    public function init()
    {
        if (!Config::has('ssl.public') || !Config::has('ssl.private')) {
            return;
        }
        $this->public = Config::get('ssl.public', $this->public);
        $this->private = Config::get('ssl.private', $this->private);
        $this->timeout = Config::get('ssl.timeout', 7200);
    }


    /**
     * @param string $publicKey
     */
    public function setPublic(string $publicKey)
    {
        $this->public = $publicKey;
    }

    /**
     * @param $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param $timeout
     */
    public function setKey(string $timeout)
    {
        $this->key = $timeout;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivate(string $privateKey)
    {
        $this->private = $privateKey;
    }


    /**
     * @param int $unionId
     * @param array $headers
     *
     * @return array
     * @throws Exception
     */
    public function create(int $unionId, $headers = []): array
    {
        $this->user = $unionId;
        $this->config['time'] = time();
        if (empty($headers)) {
            $headers = request()->headers->getHeaders();
        } else if ($headers instanceof HttpHeaders) {
            $headers = $headers->getHeaders();
        }

        $this->data = $headers;
        if (empty($unionId)) {
            throw new AuthException('您还未登录或已登录超时');
        }
        $source = $header['source'] ?? 'browser';
        if (empty($source) || !in_array($source, $this->source)) {
            throw new Exception('未知的登录设备');
        }
        return $this->createEncrypt($unionId);
    }

    /**
     * @param $unionId
     * @return array
     * @throws Exception
     * 对相关信息进行加密
     */
    private function createEncrypt($unionId): array
    {
        $caches = $this->clear($unionId);
        $param = $this->assembly(array_merge($this->config, [
            'user'  => $unionId,
            'token' => $this->token($unionId, [
                'device' => Str::rand(128),
            ], $this->config['time']),
        ]), TRUE);
        $refresh = array_intersect_key($param, $this->config);

        $params['user'] = $this->user;
        $params['token'] = $refresh['token'];
        $json = json_encode($params, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);

        openssl_private_encrypt($json, $encode, $this->private);
        $refresh['refresh'] = base64_encode($encode);
        $this->setRefresh($refresh['refresh']);

        $redis = $this->getRedis();
        foreach ($caches as $cache) {
            $redis->del($cache);
        }

        return $refresh;
    }

    /**
     * @param bool $update
     * @param array $param
     * @return array
     * @throws
     */
    private function assembly(array $param, $update = FALSE): array
    {
        if (isset($param['sign'])) {
            unset($param['sign']);
        }
        $param = $this->initialize($param);
        asort($param, SORT_STRING);
        $_tmp = [];
        foreach ($param as $key => $val) {
            $_tmp[] = trim($key) . '=>' . trim((string)$val);
        }
        $param['sign'] = md5(implode(':', $_tmp));
        if ($update) {
            $this->setCache($param);
        }
        return $param;
    }

    /**
     * @param array $headers
     * @return array
     * @throws Exception
     */
    public function refresh($headers = []): array
    {
        $this->data = $headers;
        if (!openssl_public_decrypt(base64_decode($headers['refresh']), $data, $this->public)) {
            throw new AuthException('信息解码失败.');
        }

        $this->user = $data['user'];

        if (!$this->getRedis()->exists('refresh:' . $this->user)) {
            throw new AuthException('refresh data error.');
        }

        $this->getRedis()->del('refresh:' . $this->user);

        return $this->create($this->user, $headers);
    }

    /**
     * @param $param
     *
     * @return array
     */
    private function initialize(array $param): array
    {
        $_param = [
            'version' => '1',
            'source'  => $this->getSource(),
        ];
        if (!isset($param['device'])) {
            $param['device'] = Str::rand(128);
        }
        return array_merge($_param, $param);
    }

    /**
     * @param array $data
     * @throws Exception
     */
    private function setCache(array $data)
    {
        $redis = $this->getRedis();
        $redis->hMset($this->authKey($this->getSource(), $data['token']), $data);
        $redis->expire($this->authKey($this->getSource(), $data['token']), $this->timeout);
    }

    /**
     * @param string $refresh
     * @throws Exception
     */
    private function setRefresh(string $refresh)
    {
        $redis = $this->getRedis();

        $redis->set('refresh:' . $this->user, $refresh);
        $redis->expire('refresh:' . $this->user, $this->timeout);
    }

    /**
     * @param string $_source
     * @param string $token
     *
     * @return string
     * @throws Exception
     */
    private function authKey(string $_source, string $token): string
    {
        $source = $this->getSource();
        if (!empty($_source)) $source = $_source;
        if (empty($source)) {
            throw new AuthException("未知的登陆设备");
        }
        return 'Tmp_Token:' . strtoupper($source) . ':' . $token;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->data['source'] ?? 'browser';
    }

    /**
     * @param int $user
     * @param array $param
     * @param null $requestTime
     *
     * @return string
     */
    private function token(int $user, $param = [], $requestTime = NULL): string
    {
        $str = '';

        $user = (string)$user;
        $_user = str_split(md5($user . md5($user)));
        ksort($_user);
        foreach ($_user as $key => $val) {
            $str .= md5(sha1($key . $val . $this->key));
        }
        foreach ($param as $key => $val) {
            $str .= md5($str . sha1($key . md5($val)));
        }
        $str .= sha1(base64_encode((string)$requestTime));
        return $this->preg(md5($str . $user));
    }

    /**
     * @param string $str
     *
     * @return array|string|null 将字符串替换成指定格式
     */
    private function preg(string $str): null|array|string
    {
        return preg_replace('/(\w{10})(\w{3})(\w{4})(\w{9})(\w{6})/', '$1-$2-$3-$4-$5', $str);
    }

    /**
     * @param int $user
     * @return string[]
     * @throws Exception
     */
    public function clear(int $user): array
    {
        $this->user = $user;
        $redis = $this->getRedis();
        if (is_bool($refresh = $redis->get('refresh:' . $this->user))) {
            return [];
        };
        openssl_public_decrypt(base64_decode($refresh), $info, $this->public);

        $_tmp = [];
        if (!empty($info) && $json = json_decode($info, true)) {
            if (!isset($json['token'])) {
                return [];
            }
            foreach ($this->source as $value) {
                $_tmp[] = $this->authKey($value, $json['token']);
            }
        }
        return $_tmp;
    }

    /**
     * @param array $data
     * @param int $user
     * @return bool
     * @throws AuthException
     */
    public function check(array $data, int $user): bool
    {
        $this->data = $data;
        $this->user = $user;

        if (empty($this->user)) return FALSE;
        $cache = $this->getUserModel();
        if (empty($cache)) {
            return FALSE;
        }

        $merge = $this->assembly(array_merge($cache, [
            'token' => $data['token'],
        ]));
        $check = array_diff_assoc($this->initialize($cache), $merge);
        return !((bool)count($check));
    }

    /**
     * @return mixed
     * @throws
     */
    public function getCurrentOnlineUser(): int
    {
        $this->data = request()->headers->getHeaders();

        return $this->loadByCache();
    }


    /**
     * @param string $token
     * @param string $source
     * @return mixed
     * @throws AuthException
     */
    public function getOnlineUserByToken(string $token, string $source = 'BROWSER'): int
    {
        $this->data['token'] = $token;
        $this->data['source'] = $source;

        return $this->loadByCache();
    }


    /**
     * @return int
     * @throws AuthException
     * @throws Exception
     */
    private function loadByCache(): int
    {
        $model = $this->getUserModel();
        if (empty($model)) {
            throw new AuthException('授权信息已过期！');
        }
        if (!isset($model['user'])) {
            throw new AuthException('授权信息错误！');
        }
        if (!$this->check($this->data, (int)$model['user'])) {
            throw new AuthException('授权信息不合法！');
        }

        $this->expireRefresh();

        return (int)$model['user'];
    }


    /**
     * @param array $header
     * @return mixed
     * @throws AuthException
     * @throws Exception
     */
    public static function checkAuth(array $header = []): mixed
    {
        $instance = Snowflake::app()->getJwt();
        if (empty($header)) {
            $header = request()->headers->getHeaders();
        }

        $instance->data = $header;
        $model = $instance->getUserModel();
        if (empty($model) || !isset($model['user'])) {
            return false;
        }

        if (!$instance->check($header, (int)$model['user'])) {
            return false;
        }
        $instance->expireRefresh();
        return $model['user'];
    }

    /**
     * @param null $token
     * @param null $source
     * @throws Exception
     */
    public function expireRefresh($token = null, $source = null)
    {
        if (!empty($token)) {
            $this->data['token'] = $token;
        }
        if (!empty($source)) {
            $this->data['source'] = $source;
        }
        $key = $this->authKey($this->getSource(), $this->data['token']);
        $this->getRedis()->expire($key, $this->timeout);
    }

    /**
     * @return bool|array
     * @throws AuthException
     * @throws Exception
     */
    private function getUserModel(): bool|array
    {
        if (!isset($this->data['token'])) {
            throw new AuthException('暂无访问权限！');
        }
        $key = $this->authKey($this->getSource(), $this->data['token']);
        return $this->getRedis()->hGetAll($key);
    }

    /**
     * @return Redis|\Redis
     * @throws
     */
    private function getRedis(): Redis|\Redis
    {
        return Snowflake::app()->getRedis();
    }

}
