<?php


namespace Server\Protocol;


abstract class Protocol
{


    /**
     * @param $data
     * @return array
     */
    public function resolveProtocol($data)
    {
        $explode = explode("\r\n\r\n", $data);

        $http_protocol = [];
        foreach (explode("\r\n", $explode[0]) as $key => $datum) {
            if (empty($datum) || $key == 0) {
                continue;
            }
            [$key, $value] = explode(': ', $datum);

            $http_protocol[trim($key)] = trim($value);
        }
        return [$http_protocol, $explode[1]];
    }


}
