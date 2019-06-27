<?php declare(strict_types=1);
/**
 * Copyright (c) 2019.
 */

namespace Metahash;

use Exception;
use FG\ASN1\Exception\ParserException;

/**
 * Class MetaHash
 *
 * @package Metahash
 */
class MetaHash
{
    public const HISTORY_LIMIT = 9999;

    private const CURLOPT_CONNECTTIMEOUT = 2;
    private const CURLOPT_TIMEOUT = 300;

    public const NODE_PROXY = 'PROXY';
    public const NODE_TORRENT = 'TORRENT';

    /**
     * @var string
     */
    public $network;
    /**
     * @var MetaHashCrypto
     */
    private $ecdsa;
    /**
     * @var false|resource
     */
    private $curl;
    /**
     * @var array
     */
    private $proxy = ['url' => 'proxy.net-%s.metahashnetwork.com', 'port' => 9999];
    /**
     * @var array
     */
    private $torrent = ['url' => 'tor.net-%s.metahashnetwork.com', 'port' => 5795];
    /**
     * @var array
     */
    private $hosts = [];

    /**
     * Crypto constructor.
     *
     * @param MetaHashCrypto $ecdsa
     */
    public function __construct(MetaHashCrypto $ecdsa)
    {
        $this->ecdsa = $ecdsa;
        $this->curl = \curl_init();
    }

    /**
     * Metahash key generate
     *
     * @return array
     */
    public function generateKey(): array
    {
        return $this->ecdsa->generateKey();
    }

    /**
     * Validate address
     *
     * @param string $address
     *
     * @return bool
     */
    public function checkAddress(string $address): bool
    {
        return $this->ecdsa->checkAdress($address);
    }

    /**
     * @param string $address
     *
     * @return mixed
     * @throws Exception
     *
     * @deprecated
     */
    public function fetchFullHistory(string $address)
    {
        $result['balance'] = $this->fetchBalance($address)['result'];
        if ($result['balance']['count_txs'] <= self::HISTORY_LIMIT) {
            $result['result'] = $this->fetchHistory($address)['result'];
        } else {
            $result['result'] = [];
            for ($begin = 1; $begin <= $result['balance']['count_txs']; $begin += self::HISTORY_LIMIT) {
                // @todo need fix
                $result['result'] = \array_merge(
                    $result['result'],
                    $this->fetchHistory($address, $begin, self::HISTORY_LIMIT)['result']
                );
            }
        }

        return $result;
    }

    /**
     * @see https://github.com/xboston/metahash-api#fetch-balance
     *
     * @param string $address
     *
     * @return array
     * @throws Exception
     */
    public function fetchBalance(string $address): array
    {
        return $this->queryTorrent('fetch-balance', ['address' => $address]);
    }

    /**
     * Send request to torrent node
     *
     * @param string $method
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function queryTorrent(string $method, array $data = []): array
    {
        $url = $this->getConnectionAddress(self::NODE_TORRENT);

        $query = [
            'id'     => \time(),
            'method' => \trim($method),
            'params' => $data,
        ];
        $query = \json_encode($query);

        $curl = $this->curl;
        \curl_setopt($curl, CURLOPT_URL, $url);
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
        \curl_setopt($curl, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
        \curl_setopt($curl, CURLOPT_POST, 1);
        \curl_setopt($curl, CURLOPT_POSTFIELDS, $query);

        $result = \curl_exec($curl);

        if ($result === false) {
            throw new \RuntimeException('cURL Error: '.\curl_error($curl));
        }

        return \json_decode($result, true);
    }

    /**
     * Get node IP's
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360008219634-Metahash-networks
     *
     * @param string $nodeName
     *
     * @return mixed
     * @throws Exception
     */
    public function getConnectionAddress(string $nodeName)
    {
        if (isset($this->hosts[$nodeName])) {
            return $this->hosts[$nodeName];
        }

        switch ($nodeName) {
            case self::NODE_PROXY:
                $nodeUrl = \sprintf($this->proxy['url'], $this->network);
                $nodePort = $this->proxy['port'];
                break;
            case self::NODE_TORRENT:
                $nodeUrl = \sprintf($this->torrent['url'], $this->network);
                $nodePort = $this->torrent['port'];
                break;
            default:
                throw new \RuntimeException('Unknown node type. Type '.$nodeName);
                break;
        }

        $hostsList = \dns_get_record($nodeUrl, DNS_A);
        foreach ($hostsList as $hostData) {
            if ($res = $this->checkHost($hostData['ip'].':'.$nodePort)) {
                $this->hosts[$nodeName] = $hostData['ip'].':'.$nodePort;

                return $this->hosts[$nodeName];
            }
        }

        throw new \RuntimeException('The nodes is not available. Maybe you have problems with DNS.');
    }

    /**
     * Node availability check
     *
     * @param string $host
     *
     * @return bool
     */
    public function checkHost(string $host): bool
    {
        $curl = $this->curl;
        \curl_setopt($curl, CURLOPT_URL, $host);
        \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
        \curl_setopt($curl, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
        \curl_setopt($curl, CURLOPT_NOBODY, true);
        \curl_exec($curl);
        $code = \curl_getinfo($curl, CURLINFO_HTTP_CODE);

        return ($code > 0 && $code < 500);
    }

    /**
     * Get address transaction history
     *
     * @see https://github.com/xboston/metahash-api#fetch-history
     *
     * @param string $address
     * @param int $beginTx
     * @param int $countTx
     *
     * @return bool|mixed|string
     * @throws Exception
     */
    public function fetchHistory(string $address, int $beginTx = 0, int $countTx = self::HISTORY_LIMIT)
    {
        if ($countTx > self::HISTORY_LIMIT) {
            throw new \RuntimeException('Too many transaction in one request. Maximum is '.self::HISTORY_LIMIT);
        }

        return $this->queryTorrent(
            'fetch-history',
            [
                'address'  => $address,
                'beginTx'  => $beginTx,
                'countTxs' => $countTx,
            ]
        );
    }

    /**
     * Get blocks count on torrent node
     *
     * @see https://github.com/xboston/metahash-api#get-count-blocks
     *
     * @param string $host
     *
     * @return int
     */
    public function getCountBlocks(string $host): int
    {
        $curl = $this->curl;
        \curl_setopt($curl, CURLOPT_URL, $host);
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
        \curl_setopt($curl, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
        \curl_setopt($curl, CURLOPT_POST, 1);
        \curl_setopt($curl, CURLOPT_POSTFIELDS, '{"id":"1","method":"get-count-blocks","params":[]}');
        $res = \curl_exec($curl);

        if ($res === false) {
            return 0;
        }

        $res = \json_decode($res, true);

        return (int)($res['result']['count_blocks'] ?? 0);
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-tx
     *
     * @param string $hash
     *
     * @return array
     * @throws Exception
     */
    public function getTx(string $hash): array
    {
        return $this->queryTorrent('get-tx', ['hash' => $hash]);
    }

    /**
     * Get nonce param for address
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360003271694-Creating-transactions
     *
     * @param string $address
     *
     * @return int
     * @throws Exception
     */
    public function getNonce(string $address): int
    {
        $res = $this->fetchBalance($address);

        return isset($res['result']['count_spent']) ? (int)$res['result']['count_spent'] + 1 : 1;
    }

    /**
     * Signature data
     *
     * @param string $sign_text
     * @param string $private_key
     *
     * @return string
     * @throws ParserException
     */
    public function sign(string $sign_text, string $private_key): string
    {
        return $this->ecdsa->sign($sign_text, $private_key);
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-block-by-number
     *
     * @param int $number
     * @param int $type
     *
     * @return array
     * @throws Exception
     */
    public function getBlockByNumber(int $number, int $type): array
    {
        return $this->queryTorrent('get-block-by-number', ['number' => $number, 'type' => $type]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-block-by-hash
     *
     * @param string $hash
     * @param int $type
     *
     * @return array
     * @throws Exception
     */
    public function getBlockByHash(string $hash, int $type): array
    {
        return $this->queryTorrent('get-block-by-hash', ['hash' => $hash, 'type' => $type]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#mhc_send
     *
     * @param string $to
     * @param string $value
     * @param string $fee
     * @param int $nonce
     * @param string $data
     * @param string $key
     * @param string $sign
     *
     * @return array
     * @throws Exception
     */
    public function sendTx(string $to, string $value, string $fee = '', int $nonce = 1, string $data = '', string $key = '', string $sign = ''): array
    {
        $txData = [
            'to'     => $to,
            'value'  => $value,
            'fee'    => $fee,
            'nonce'  => $nonce,
            'data'   => $data,
            'pubkey' => $key,
            'sign'   => $sign,
        ];

        return $this->queryProxy('mhc_send', $txData);
    }

    /**
     * Send request to proxy node
     *
     * @param string $method
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    private function queryProxy(string $method, array $data = []): array
    {
        $query = [
            'id'      => \time(),
            'version' => '2.0',
            'method'  => \trim($method),
            'params'  => $data,
        ];

        $query = \json_encode($query);
        $nodeURL = $this->getConnectionAddress(self::NODE_PROXY);

        $curl = $this->curl;
        \curl_setopt($curl, CURLOPT_URL, $nodeURL);
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
        \curl_setopt($curl, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
        \curl_setopt($curl, CURLOPT_POST, 1);
        \curl_setopt($curl, CURLOPT_POSTFIELDS, $query);

        $result = \curl_exec($curl);

        return \json_decode($result, true);
    }
}
