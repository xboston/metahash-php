<?php declare(strict_types=1);
/**
 * Copyright (c) 2019.
 */

namespace Metahash;

use Exception;
use FG\ASN1\Exception\ParserException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Class MetaHash
 *
 * @package Metahash
 */
class MetaHash
{
    public const HISTORY_LIMIT = 9999;

    private const CONNECT_TIMEOUT = 2;
    private const TIMEOUT = 150;
    private const DEBUG = false;

    public const NODE_PROXY = 'PROXY';
    public const NODE_TORRENT = 'TORRENT';

    public const NETWORK_MAIN = 'main';
    public const NETWORK_TEST = 'test';
    public const NETWORK_DEV = 'dev';

    /**
     * @var string
     */
    private $network;
    /**
     * @var MetaHashCrypto
     */
    private $ecdsa;
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
     * @var GuzzleClient
     */
    private $client;

    /**
     * Crypto constructor.
     *
     */
    public function __construct()
    {
        $guzzleOptions = [
            'timeout'         => self::TIMEOUT,
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'debug'           => self::DEBUG,
        ];
        $this->setClient(new GuzzleClient($guzzleOptions));
        $this->setNetwork(self::NETWORK_MAIN);
    }

    /**
     * Metahash key generate
     *
     * @return array
     */
    public function generateKey(): array
    {
        return $this->getEcdsa()->generateKey();
    }

    /**
     * @return MetaHashCrypto
     */
    public function getEcdsa(): MetaHashCrypto
    {
        if ($this->ecdsa === null) {
            $this->ecdsa = new MetaHashCrypto();
        }

        return $this->ecdsa;
    }

    /**
     * @param MetaHashCrypto $ecdsa
     */
    public function setEcdsa(MetaHashCrypto $ecdsa): void
    {
        $this->ecdsa = $ecdsa;
    }

    /**
     * Validate address
     *
     * @param string $address
     *
     * @param bool $fast
     *
     * @return bool
     */
    public function checkAddress(string $address, bool $fast = false): bool
    {
        if ($fast) {
            return \strpos($address, '0x0') === 0 && \strlen($address) === 52;
        }

        return $this->getEcdsa()->checkAdress($address);
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
     * @throws GuzzleException
     */
    public function fetchHistory(string $address, int $beginTx = 0, int $countTx = self::HISTORY_LIMIT)
    {
        if ($countTx > self::HISTORY_LIMIT) {
            throw new RuntimeException('Too many transaction. Maximum is '.self::HISTORY_LIMIT);
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
     * Send request to torrent node
     *
     * @param string $method
     * @param array $params
     *
     * @return array
     * @throws GuzzleException
     */
    public function queryTorrent(string $method, array $params = []): array
    {
        $url = $this->getConnectionAddress(self::NODE_TORRENT);

        return $this->query($url, $method, $params);
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
     * @throws GuzzleException
     */
    public function getConnectionAddress(string $nodeName)
    {
        if (isset($this->hosts[$nodeName])) {
            return $this->hosts[$nodeName];
        }

        switch ($nodeName) {
            case self::NODE_PROXY:
                $nodeUrl = \sprintf($this->proxy['url'], $this->getNetwork());
                $nodePort = $this->proxy['port'];
                break;
            case self::NODE_TORRENT:
                $nodeUrl = \sprintf($this->torrent['url'], $this->getNetwork());
                $nodePort = $this->torrent['port'];
                break;
            default:
                throw new RuntimeException('Unknown node type. Type '.$nodeName);
                break;
        }

        $hostsList = \dns_get_record($nodeUrl, DNS_A);
        foreach ($hostsList as $hostData) {
            if ($res = $this->checkHost($hostData['ip'].':'.$nodePort)) {
                $this->hosts[$nodeName] = $hostData['ip'].':'.$nodePort;

                return $this->hosts[$nodeName];
            }
        }

        throw new RuntimeException('The nodes is not available. Maybe you have problems with DNS.');
    }

    /**
     * @return string
     */
    public function getNetwork(): string
    {
        return $this->network;
    }

    /**
     * @param string $network
     */
    public function setNetwork(string $network): void
    {
        $this->network = $network;
    }

    /**
     * Node availability check
     *
     * @param string $host
     *
     * @return bool
     * @throws GuzzleException
     */
    public function checkHost(string $host): bool
    {
        try {
            $response = $this->client->request('HEAD', $host);
            $code = $response->getStatusCode();

            return ($code > 0 && $code < 500);
        } catch (Exception $e) {
            return $e->getCode() === 400;
        }
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $params
     *
     * @return array
     * @throws GuzzleException
     */
    private function query(string $url, string $method, array $params = []): array
    {
        $query = [
            'id'     => \time(),
            'method' => \trim($method),
            'params' => $params,
        ];
        $query = \json_encode($query);

        $response = $this->client->request('POST', $url, ['body' => $query]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('QueryTorrent Error: '.$response->getBody()->getContents());
        }

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get blocks count on torrent node
     *
     * @see https://github.com/xboston/metahash-api#get-count-blocks
     *
     *
     * @return array
     * @throws GuzzleException
     */
    public function getCountBlocks(): array
    {
        return $this->queryTorrent('get-count-blocks');
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-tx
     *
     * @param string $hash
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
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
     * @throws GuzzleException
     */
    public function getNonce(string $address): int
    {
        $res = $this->fetchBalance($address);

        return isset($res['result']['count_spent']) ? (int)$res['result']['count_spent'] + 1 : 1;
    }

    /**
     * @see https://github.com/xboston/metahash-api#fetch-balance
     *
     * @param string $address
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function fetchBalance(string $address): array
    {
        return $this->queryTorrent('fetch-balance', ['address' => $address]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#fetch-balances
     *
     * @param array $addresses
     *
     * @return array
     * @throws GuzzleException
     */
    public function fetchBalances(array $addresses): array
    {
        return $this->queryTorrent('fetch-balances', ['addresses' => $addresses]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-last-txs
     *
     * @return array
     * @throws GuzzleException
     */
    public function getLastTxs(): array
    {
        return $this->queryTorrent('get-last-txs');
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
        return $this->getEcdsa()->sign($sign_text, $private_key);
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-block-by-number
     *
     * @param int $number
     * @param int $type
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
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
     * @throws GuzzleException
     */
    public function getBlockByHash(string $hash, int $type): array
    {
        return $this->queryTorrent('get-block-by-hash', ['hash' => $hash, 'type' => $type]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-blocks
     *
     * @param int $beginBlock
     * @param int $countBlocks
     *
     * @return array
     * @throws GuzzleException
     */
    public function getBlocks(int $beginBlock, int $countBlocks): array
    {
        return $this->queryTorrent('get-blocks', ['beginBlock' => $beginBlock, 'countBlocks' => $countBlocks]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#status
     *
     * @return array
     * @throws GuzzleException
     */
    public function status(): array
    {
        return $this->queryTorrent('status');
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-dump-block-by-number
     *
     * @param int $number
     * @param bool $isHex
     *
     * @return array
     * @throws GuzzleException
     */
    public function getDumpBlockByNumber(int $number, bool $isHex = false): array
    {
        return $this->queryTorrent('get-dump-block-by-number', ['number' => $number, 'isHex' => $isHex]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-dump-block-by-hash
     *
     * @param int $number
     * @param bool $isHex
     *
     * @return array
     * @throws GuzzleException
     */
    public function getDumpBlockByHash(int $number, bool $isHex = false): array
    {
        return $this->queryTorrent('get-dump-block-by-hash', ['number' => $number, 'isHex' => $isHex]);
    }

    /**
     * @see https://github.com/xboston/metahash-api#getinfo
     *
     * @return array
     * @throws GuzzleException
     */
    public function getInfo(): array
    {
        return $this->queryProxy('getinfo');
    }

    /**
     * Send request to proxy node
     *
     * @param string $method
     * @param array $params
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function queryProxy(string $method, array $params = []): array
    {
        $url = $this->getConnectionAddress(self::NODE_PROXY);

        return $this->query($url, $method, $params);
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
     * @throws GuzzleException
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
     * @return GuzzleClient
     */
    public function getClient(): GuzzleClient
    {
        return $this->client;
    }

    /**
     * @param GuzzleClient $client
     */
    public function setClient(GuzzleClient $client): void
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    /**
     * @param array $hosts
     */
    public function setHosts(array $hosts): void
    {
        $this->hosts = $hosts;
    }
}
