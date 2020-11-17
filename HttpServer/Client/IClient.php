<?php


namespace HttpServer\Client;


interface IClient
{


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function get(string $path, array $params = []);


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function post(string $path, array $params = []);


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function delete(string $path, array $params = []);


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function options(string $path, array $params = []);


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function upload(string $path, array $params = []);


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function put(string $path, array $params = []);


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function head(string $path, array $params = []);


	/**
	 * @param string $method
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function request(string $method, string $path, array $params = []);


	/**
	 * @param string $host
	 * @return mixed
	 */
	public function setHost(string $host);


	/**
	 * @param array $header
	 * @return mixed
	 */
	public function setHeader(array $header);


	/**
	 * @param array $header
	 * @return mixed
	 */
	public function setHeaders(array $header);


	/**
	 * @param string $key
	 * @param string $value
	 * @return mixed
	 */
	public function addHeader(string $key, string $value);


	/**
	 * @param int $value
	 * @return mixed
	 */
	public function setTimeout(int $value);


	/**
	 * @param \Closure|null $value
	 * @return mixed
	 */
	public function setCallback(?\Closure $value);


	/**
	 * @param string $value
	 * @return mixed
	 */
	public function setMethod(string $value);


	/**
	 * @param bool $isSSL
	 * @return mixed
	 */
	public function setIsSSL(bool $isSSL);


	/**
	 * @param string $agent
	 * @return mixed
	 */
	public function setAgent(string $agent);


	/**
	 * @param string $errorCodeField
	 * @return mixed
	 */
	public function setErrorCodeField(string $errorCodeField);


	/**
	 * @param string $errorMsgField
	 * @return mixed
	 */
	public function setErrorMsgField(string $errorMsgField);


	/**
	 * @param bool $use_swoole
	 * @return mixed
	 */
	public function setUseSwoole(bool $use_swoole);


	/**
	 * @param string $ssl_cert_file
	 * @return mixed
	 */
	public function setSslCertFile(string $ssl_cert_file);


	/**
	 * @param string $ssl_key_file
	 * @return mixed
	 */
	public function setSslKeyFile(string $ssl_key_file);


	/**
	 * @param string $ssl_key_file
	 * @return mixed
	 */
	public function setCa(string $ssl_key_file);


	/**
	 * @param int $port
	 * @return mixed
	 */
	public function setPort(int $port);


	/**
	 * @param string $message
	 * @return mixed
	 */
	public function setMessage(string $message);


	/**
	 * @param string $data
	 * @return mixed
	 */
	public function setData(string $data);


	/**
	 * @param int $connect_timeout
	 * @return mixed
	 */
	public function setConnectTimeout(int $connect_timeout);


}
