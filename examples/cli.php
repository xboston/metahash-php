<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Metahash\Crypto;
use Metahash\Ecdsa;

try {
    $args = [];

    \parse_str(\implode('&', \array_slice($argv, 1)), $args);

    $args['method'] = isset($args['method']) && !empty($args['method']) ? \strtolower($args['method']) : null;
    $args['net'] = isset($args['net']) && !empty($args['net']) ? \strtolower($args['net']) : null;
    $args['address'] = isset($args['address']) && !empty($args['address']) ? \strtolower($args['address']) : null;
    $args['hash'] = isset($args['hash']) && !empty($args['hash']) ? \strtolower($args['hash']) : null;
    $args['to'] = isset($args['to']) && !empty($args['to']) ? \strtolower($args['to']) : null;
    $args['value'] = isset($args['value']) && !empty($args['value']) ? \number_format($args['value'], 0, '', '') : 0;
    $args['fee'] = '';//isset($args['fee']) && !empty($args['fee'])?number_format($args['fee'], 0, '', ''):0;
    $args['data'] = isset($args['data']) && !empty($args['data']) ? \trim($args['data']) : '';
    $args['nonce'] = isset($args['nonce']) && !empty($args['nonce']) ? (int)$args['nonce'] : 0;

    if (empty($args['method']) || $args['method'] === null) {
        throw new \RuntimeException('method is empty', 1);
    }

    $crypto = new Crypto(new Ecdsa());
    $crypto->net = $args['net'];

    switch ($args['method']) {
        case 'generate':
            $result = $crypto->generateKey();
            echo \json_encode($result, JSON_PRETTY_PRINT);
            break;

        case 'fetch-balance':
            if (empty($args['address'])) {
                throw new \RuntimeException('address is empty', 1);
            }

            if ($crypto->checkAddress($args['address']) === false) {
                throw new \RuntimeException('invalid address value', 1);
            }

            echo \json_encode($crypto->fetchBalance($args['address']), JSON_PRETTY_PRINT);
            break;

        case 'fetch-history':
            if (empty($args['address'])) {
                throw new \RuntimeException('address is empty', 1);
            }

            if ($crypto->checkAddress($args['address']) === false) {
                throw new \RuntimeException('invalid address value', 1);
            }

            echo \json_encode($crypto->fetchHistory($args['address']), JSON_PRETTY_PRINT);
            break;

        case 'get-tx':
            if (empty($args['hash'])) {
                throw new \RuntimeException('hash is empty', 1);
            }

            echo \json_encode($crypto->getTx($args['hash']), JSON_PRETTY_PRINT);
            break;

        default:
            throw new \RuntimeException('unknown method', 1);
            break;
    }
} catch (Exception $e) {
    echo \json_encode(['error' => true, 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}
