<?php
declare(strict_types=1);


namespace Kiri\Core;


use Exception;


/**
 * Class Help
 * @package Kiri\Kiri\Core
 */
class Help
{

	/**
	 * @param array $data
	 * @return string
	 */
	public static function toXml(array $data): string
	{
		$xml = "<xml>";
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$xml .= "<" . $key . ">" . static::xmlChild($val) . "</" . $key . ">";
			} else if (is_numeric($val)) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";
		return $xml;
	}


	/**
	 * @param array $array
	 * @return string
	 */
	private static function xmlChild(array $array): string
	{
		$string = '';
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$string .= static::xmlChild($value);
			} else if (is_numeric($value)) {
				$string .= "<" . $key . ">" . $value . "</" . $key . ">";
			} else {
				$string .= "<" . $key . "><![CDATA[" . $value . "]]></" . $key . ">";
			}
		}
		return $string;
	}


	/**
	 * @param $xml
	 * @return mixed
	 */
	public static function toArray($xml): mixed
	{
		if (empty($xml)) {
			return [];
		} else if (is_array($xml)) {
			return $xml;
		}
		if (!($_xml = Xml::isXml($xml))) {
			return static::jsonToArray($xml);
		}
		return $_xml;
	}


	/**
	 * @param $xml
	 * @return mixed
	 */
	public static function jsonToArray($xml): mixed
	{
		$_xml = json_decode($xml, true);
		if (is_null($_xml)) {
			return [];
		}
		return $_xml;
	}

	/**
	 * @param $xml
	 * @return mixed
	 */
	public static function xmlToArray($xml): mixed
	{
		if (is_array($xml)) {
			return $xml;
		}
		if (($data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)) !== false) {
			return json_decode(json_encode($data), TRUE);
		}
		if (!is_null($json = json_decode($xml, TRUE))) {
			return $json;
		}
		return $xml;
	}

	/**
	 * @param $parameter
	 * @return array|false|string
	 * @throws Exception
	 */
	public static function toString($parameter): bool|array|string
	{
		if (!is_string($parameter)) {
			$parameter = ArrayAccess::toArray($parameter);
			if (is_array($parameter)) {
				$parameter = Json::encode($parameter);
			}
		}
		return $parameter;
	}

	/**
	 * @param mixed $json
	 * @return bool|string
	 */
	public static function toJson(mixed $json): bool|string
	{
		if (is_object($json)) {
			$json = get_object_vars($json);
		}
		if (is_array($json)) {
			return json_encode($json, JSON_UNESCAPED_UNICODE);
		}
		$matchQuote = '/(<\?xml.*?\?>)?<([a-zA-Z_]+)>(<([a-zA-Z_]+)><!.*?><\/\4>)+<\/\2>/';
		if (preg_match($matchQuote, $json)) {
			$json = self::xmlToArray($json);
		} else {
			$json = json_decode($json, true);
		}
		if (!is_array($json)) {
			$json = [];
		}
		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param int $length
	 * @return string
	 *
	 * 随机字符串
	 */
	public static function random($length = 20): string
	{
		$res = [];
		$str = 'abcdefghijklmnopqrstuvwxyz';
		$str .= strtoupper($str) . '1234567890';
		for ($i = 0; $i < $length; $i++) {
			$rand = substr($str, rand(0, strlen($str) - 2), 1);
			if (empty($rand)) {
				$rand = substr($str, strlen($str) - 3, 1);
			}
			array_push($res, $rand);
		}

		return implode($res);
	}

	/**
	 * @param array $array
	 * @param $key
	 * @param string $type
	 * @return string
	 */
	public static function sign(array $array, $key, string $type = 'MD5'): string
	{
		ksort($array, SORT_ASC);
		$string = [];
		foreach ($array as $hashKey => $val) {
			if (empty($val)) {
				continue;
			}
			$string[] = $hashKey . '=' . $val;
		}
		$string[] = 'key=' . $key;
		$string = implode('&', $string);
		if ($type == 'MD5') {
			return strtoupper(md5($string));
		} else {
			return hash('sha256', $string);
		}
	}


	/**
	 * @param $email
	 * @param string $Subject
	 * @param $messageContent
	 */
	public static function sendEmail($email, string $Subject, $messageContent)
	{
		$mailer = new \Swift_Mailer((new \Swift_SmtpTransport($email['host'], $email['port']))
			->setUsername($email['username'])->setPassword($email['password']));
		$message = (new \Swift_Message($Subject))
			->setFrom([$email['send']['address'] => $email['send']['nickname']])
			->setBody('Here is the message itself');

		foreach ($email['receive'] as $item) {
			$message->setTo([$item['address'], $item['address'] => $item['nickname']]);
		}
		$mailer->send($messageContent);
	}

}
