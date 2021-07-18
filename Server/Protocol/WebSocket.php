<?php

namespace Server\Protocol;


class WebSocket extends Protocol
{


    //
    public function decode($received): ?string
    {
        $decoded = null;
        $buffer = $received;
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else {
            if ($len === 127) {
                $masks = substr($buffer, 10, 4);
                $data = substr($buffer, 14);
            } else {
                $masks = substr($buffer, 2, 4);
                $data = substr($buffer, 6);
            }
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return $decoded;
    }

    const BINARY_TYPE_BLOB = "\x81";


    public function encode($buffer): string
    {
        $len = strlen($buffer);

        $first_byte = self::BINARY_TYPE_BLOB;

        if ($len <= 125) {
            $encode_buffer = $first_byte . chr($len) . $buffer;
        } else {
            if ($len <= 65535) {
                $encode_buffer = $first_byte . chr(126) . pack("n", $len) . $buffer;
            } else {
                //pack("xxxN", $len)pack函数只处理2的32次方大小的文件，实际上2的32次方已经4G了。
                $encode_buffer = $first_byte . chr(127) . pack("xxxxN", $len) . $buffer;
            }
        }

        return $encode_buffer;
    }


    /**
     * @param $server
     * @param $fd
     * @param $data
     */
    private function getWebSocketProtocol($data)
    {
        [$http_protocol, $body] = $this->resolveProtocol($data);
        $key = base64_encode(sha1($http_protocol['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE));
        $headers = [
            'HTTP/1.1 101 Switching Protocols',
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Accept: ' . $key,
            'Sec-WebSocket-Version: 13',
        ];
        if (isset($http_protocol['Sec-WebSocket-Protocol'])) {
            $headers[] = 'Sec-WebSocket-Protocol: ' . $http_protocol['Sec-WebSocket-Protocol'];
        }
        return implode("\r\n", $headers) . "\r\n\r\n";
    }


}
