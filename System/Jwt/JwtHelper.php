<?php

namespace Snowflake\Jwt;

trait JwtHelper
{


	/** @var int $user */
	private int $user;

	private array $data;

	private array $source = ['browser', 'android', 'iphone', 'pc', 'mingame'];

	private array $config = ['token' => ''];

	private ?int $timeout = 7200;

	private string $key = 'www.xshucai.com';


	private string $secret = '';


	private string $encrypt = 'sha256';


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
	 * @param string $publicKey
	 */
	public function setPublic(string $publicKey)
	{
		$this->public = $publicKey;
	}

	/**
	 * @param int $timeout
	 */
	public function setTimeout(int $timeout)
	{
		$this->timeout = $timeout;
	}

	/**
	 * @param string $timeout
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


}
