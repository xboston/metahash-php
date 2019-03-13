<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Metahash\Crypto;
use Metahash\Ecdsa;

try {
    $args = [];

    \parse_str(\implode('&', \array_slice($argv, 1)), $args);


    $args['method'] = isset($args['method']) && ! empty($args['method']) ? \strtolower($args['method']) : null;
    $args['net'] = isset($args['net']) && ! empty($args['net']) ? \strtolower($args['net']) : null;
    $args['address'] = isset($args['address']) && ! empty($args['address']) ? \strtolower($args['address']) : null;
    $args['hash'] = isset($args['hash']) && ! empty($args['hash']) ? \strtolower($args['hash']) : null;
    $args['to'] = isset($args['to']) && ! empty($args['to']) ? \strtolower($args['to']) : null;
    $args['value'] = isset($args['value']) && ! empty($args['value']) ? \number_format($args['value'], 0, '', '') : 0;
    $args['fee'] = '';//isset($args['fee']) && !empty($args['fee'])?number_format($args['fee'], 0, '', ''):0;
    $args['data'] = isset($args['data']) && ! empty($args['data']) ? \trim($args['data']) : '';
    $args['nonce'] = isset($args['nonce']) && ! empty($args['nonce']) ? \intval($args['nonce']) : 0;

    if (empty($args['method']) || $args['method'] === null) {
        throw new Exception('method is empty', 1);
    }

    $crypto = new Crypto(new Ecdsa());
    //$crypto->debug = true;w
    $crypto->net = $args['net'];

    switch ($args['method']) {
        case 'generate':
            //check_net_arg($args);
            $result = $crypto->generate();
            $crypto->net = 'test';
            $crypto->create($result['address']);
            echo \json_encode($result);
            break;

        case 'fetch-balance':
            check_net_arg($args);
            if ($crypto->checkAdress($args['address']) === false) {
                throw new Exception('invalid address value', 1);
            }

            echo \json_encode($crypto->fetchBalance($args['address']));
            break;

        case 'fetch-history':
            check_net_arg($args);
            if ($crypto->checkAdress($args['address']) === false) {
                throw new Exception('invalid address value', 1);
            }

            echo \json_encode($crypto->fetchHistory($args['address']));
            break;

        case 'get-tx':
            check_net_arg($args);
            if (empty($args['hash'])) {
                throw new Exception('hash is empty', 1);
            }

            echo \json_encode($crypto->getTx($args['hash']));
            break;

        case 'get-list-address':
            echo \json_encode($crypto->listAddress());
            break;

        case 'create-tx':
            //
            break;

        case 'send-tx':
            check_net_arg($args);

            if (($keys = $crypto->readAddress($args['address'])) == false) {
                throw new Exception('address file not found', 1);
            }

            $nonce = $crypto->getNonce($args['address']);

            if ($crypto->net != 'main') {
                $data_len = \strlen($args['data']);
                if ($data_len > 0) {
                    $args['fee'] = $data_len;
                    $args['data'] = str2hex($args['data']);
                }
            } else {
                $args['data'] = '';
            }

            $sign_text = $crypto->makeSign($args['to'], \strval($args['value']), \strval($nonce), \strval($args['fee']), $args['data']);
            $sign = $crypto->sign($sign_text, $keys['private']);
            $res = $crypto->sendTx($args['to'], $args['value'], $args['fee'], $nonce, $args['data'], $keys['public'], $sign);

            echo \json_encode($res);
            break;

        default:
            throw new Exception('method not found', 1);
            break;
    }
} catch (Exception $e) {
    echo \json_encode(['error' => true, 'message' => $e->getMessage()]);
}


function is_base64_encoded($data)
{
    $data = \str_replace("\r\n", '', $data);
    $chars = ['+', '=', '/', '-'];
    $n = 0;
    foreach ($chars as $val) {
        if (\strstr($data, $val)) {
            $n++;
        }
    }

    return ($n > 0 && \base64_encode(\base64_decode($data, true)) === $data) ? true : false;
}


function str2hex($string)
{
    return \implode(\unpack('H*', $string));
}

function hex2str($hex)
{
    return \pack('H*', $hex);
}
