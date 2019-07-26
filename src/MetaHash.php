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
    public const NODE_PROXY = 'PROXY';
    public const NODE_TORRENT = 'TORRENT';
    public const NETWORK_MAIN = 'main';
    public const NETWORK_TEST = 'test';
    public const NETWORK_DEV = 'dev';

    public const KEY_TYPE_SECP256R1 = 0;
    public const KEY_TYPE_SECP256K1 = 1;

    /**
     * @var int
     */
    private $connectTimeout = 2;
    /**
     * @var int
     */
    private $timeout = 150;
    /**
     * @var bool
     */
    private $debug = false;
    /**
     * @var string
     */
    private $network;
    /**
     * @var MetaHashCrypto
     */
    private $metahashCrypto;
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
     * MetaHash constructor.
     */
    public function __construct()
    {
        $guzzleOptions = [
            'timeout'         => $this->getTimeout(),
            'connect_timeout' => $this->getConnectTimeout(),
            'debug'           => $this->getDebug(),
        ];
        $this->setClient(new GuzzleClient($guzzleOptions));
        $this->setNetwork(self::NETWORK_MAIN);
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * @param int $connectTimeout
     */
    public function setConnectTimeout(int $connectTimeout): void
    {
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * Metahash key generate
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network
     *
     * @param int $keyType
     *
     * @return array
     */
    public function generateKey($keyType = MetaHash::KEY_TYPE_SECP256K1): array
    {
        return $this->getMetahashCrypto()->generateKey($keyType);
    }

    /**
     * @return MetaHashCrypto
     */
    public function getMetahashCrypto(): MetaHashCrypto
    {
        if ($this->metahashCrypto === null) {
            $this->metahashCrypto = new MetaHashCrypto();
        }

        return $this->metahashCrypto;
    }

    /**
     * @param MetaHashCrypto $metahashCrypto
     */
    public function setMetahashCrypto(MetaHashCrypto $metahashCrypto): void
    {
        $this->metahashCrypto = $metahashCrypto;
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

        return $this->getMetahashCrypto()->checkAdress($address);
    }

    /**
     * Get address transaction history
     *
     * @see https://github.com/xboston/metahash-api#fetch-history
     *
     * @param string $address
     * @param int $countTx
     * @param int $beginTx
     *
     * @return bool|mixed|string
     * @throws GuzzleException
     */
    public function fetchHistory(string $address, int $countTx = self::HISTORY_LIMIT, int $beginTx = 0)
    {
        if ($countTx > self::HISTORY_LIMIT) {
            throw new RuntimeException('Too many transaction. Maximum is '.self::HISTORY_LIMIT);
        }

        return $this->queryTorrent(
            'fetch-history',
            [
                'address'  => $address,
                'countTxs' => $countTx,
                'beginTx'  => $beginTx,
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
     * @param string $signText
     * @param string $privateKey
     *
     * @return string
     * @throws ParserException
     */
    public function sign(string $signText, string $privateKey): string
    {
        return $this->getMetahashCrypto()->sign($signText, $privateKey);
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
     * @param int $countTx
     * @param int $beginTx
     *
     * @return array
     * @throws GuzzleException
     */
    public function getBlockByHash(string $hash, int $type, int $countTx = null, int $beginTx = null): array
    {
        return $this->queryTorrent(
            'get-block-by-hash',
            [
                'hash'     => $hash,
                'type'     => $type,
                'countTxs' => $countTx,
                'beginTx'  => $beginTx,
            ]
        );
    }

    /**
     * @see https://github.com/xboston/metahash-api#get-blocks
     *
     * @param int $countBlocks
     * @param int $beginBlock
     *
     * @return array
     * @throws GuzzleException
     */
    public function getBlocks(int $countBlocks, int $beginBlock): array
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
     * @param string $hash
     * @param bool $isHex
     *
     * @return array
     * @throws GuzzleException
     */
    public function getDumpBlockByHash(string $hash, bool $isHex = false): array
    {
        return $this->queryTorrent('get-dump-block-by-hash', ['hash' => $hash, 'isHex' => $isHex]);
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
     * @param string $privateKey
     * @param string $to
     * @param int $value
     * @param string $data
     * @param int $nonce
     * @param int $fee
     *
     * @return array
     * @throws GuzzleException
     * @throws ParserException
     */
    public function sendTx(string $privateKey, string $to, int $value, string $data = '', int $nonce = 1, int $fee = 0): array
    {
        $metaHashCrypto = $this->getMetahashCrypto();
        $data = $data === '' ? $data : $metaHashCrypto->str2hex($data);
        $signText = $metaHashCrypto->makeSign($to, $value, $nonce, $fee, $data);
        $sign = $metaHashCrypto->sign($signText, $privateKey);

        $txData = [
            'to'     => $to,
            'value'  => (string)$value,
            'fee'    => (string)$fee,
            'nonce'  => (string)$nonce,
            'data'   => (string)$data,
            'pubkey' => $metaHashCrypto->privateToPublic($privateKey),
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
