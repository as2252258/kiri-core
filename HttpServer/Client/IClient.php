<?php


namespace HttpServer\Client;


interface IClient
{


    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function get(string $path, array $params = []);


    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function post(string $path, array $params = []);


    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function delete(string $path, array $params = []);


    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function options(string $path, array $params = []);


    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function upload(string $path, array $params = []);


    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function put(string $path, array $params = []);


    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function head(string $path, array $params = []);


    /**
     * @param string $method
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function request(string $method, string $path, array $params = []);

}