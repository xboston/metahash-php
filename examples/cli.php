<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Metahash\MetaHash;
use Metahash\MetaHashCrypto;

try {
    $args = [];
    \parse_str(\strtolower(\implode('&', \array_slice($argv, 1))), $args);

    $args['method'] = isset($args['method']) && ! empty($args['method']) ? $args['method'] : null;
    $args['net'] = isset($args['net']) && ! empty($args['net']) ? $args['net'] : 'main';
    $args['address'] = isset($args['address']) && ! empty($args['address']) ? $args['address'] : null;
    $args['hash'] = isset($args['hash']) && ! empty($args['hash']) ? $args['hash'] : null;

    if (empty($args['method']) || $args['method'] === null) {
        throw new \RuntimeException('method is empty', 1);
    }

    $metaHash = new MetaHash();
    $metaHash->setNetwork($args['net']);

    switch ($args['method']) {
        case 'generate':
            $metaHash->setMetahashCrypto(new MetaHashCrypto());
            $result = $metaHash->generateKey();
            echo \json_encode($result, JSON_PRETTY_PRINT);
            break;

        case 'fetch-balance':
            if (empty($args['address'])) {
                throw new \RuntimeException('address is empty', 1);
            }

            if ($metaHash->checkAddress((string)$args['address']) === false) {
                throw new \RuntimeException('invalid address value', 1);
            }

            echo \json_encode($metaHash->fetchBalance((string)$args['address']), JSON_PRETTY_PRINT);
            break;

        case 'fetch-balances':
            if (empty($args['address'])) {
                throw new \RuntimeException('address is empty', 1);
            }

            $addresess = \explode(',', $args['address']);

            \array_walk($addresess, static function ($address) use ($metaHash) {
                if ($metaHash->checkAddress((string)$address) === false) {
                    throw new \RuntimeException('invalid address value '.$address, 1);
                }
            });

            echo \json_encode($metaHash->fetchBalances($addresess), JSON_PRETTY_PRINT);
            break;

        case 'fetch-history':
            if (empty($args['address'])) {
                throw new \RuntimeException('address is empty', 1);
            }

            if ($metaHash->checkAddress((string)$args['address']) === false) {
                throw new \RuntimeException('invalid address value', 1);
            }

            echo \json_encode($metaHash->fetchHistory((string)$args['address'], 10), JSON_PRETTY_PRINT);
            break;

        case 'get-tx':
            if (empty($args['hash'])) {
                throw new \RuntimeException('hash is empty', 1);
            }

            echo \json_encode($metaHash->getTx((string)$args['hash']), JSON_PRETTY_PRINT);
            break;

        case 'get-last-txs':
            echo \json_encode($metaHash->getLastTxs(), JSON_PRETTY_PRINT);
            break;

        default:
            throw new \RuntimeException('unknown method');
            break;
    }
} catch (Exception $e) {
    echo \json_encode(['error' => true, 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}
