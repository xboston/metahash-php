<?php declare(strict_types=1);
/**
 * Copyright (c) 2019.
 */

namespace Metahash;

use Exception;
use FG\ASN1\Exception\ParserException;

/**
 * Class Crypto
 *
 * @package Metahash
 */
class Crypto
{
    public const HISTORY_LIMIT = 9999;

    private const CURLOPT_CONNECTTIMEOUT = 1;
    private const CURLOPT_TIMEOUT = 300;

    /**
     * @var string
     */
    public $net;
    /**
     * @var Ecdsa
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
     * @param Ecdsa $ecdsa
     */
    public function __construct(Ecdsa $ecdsa)
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
        $data = $this->ecdsa->generateKey();
        $data['address'] = $this->ecdsa->getAdress($data['public']);

        return $data;
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
        try {
            $query = [
                'id'     => \time(),
                'method' => \trim($method),
                'params' => $data,
            ];
            $query = \json_encode($query);
            $url = $this->getConnectionAddress('TORRENT');

            if ($url) {
                $curl = $this->curl;
                \curl_setopt($curl, CURLOPT_URL, $url);
                \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
                \curl_setopt($curl, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
                \curl_setopt($curl, CURLOPT_POST, 1);
                \curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                /* curl_setopt($curl, CURLOPT_VERBOSE, true);*/

                $result = \curl_exec($curl);

                if ($result === false) {
                    throw new \RuntimeException('cURL Error: '.\curl_error($curl));
                }
                $result = \json_decode($result, true);

                return $result;
            }
            throw new \RuntimeException('The proxy service is not available. Maybe you have problems with DNS.');
        } catch (Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Get node IP's
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360008219634-Metahash-networks
     *
     * @param string $node_type
     *
     * @return mixed
     * @throws Exception
     */
    public function getConnectionAddress(string $node_type)
    {
        if (isset($this->hosts[$node_type]) && ! empty($this->hosts[$node_type])) {
            return $this->hosts[$node_type];
        }

        $node_url = null;
        $node_port = null;

        switch ($node_type) {
            case 'PROXY':
                $node_url = \sprintf($this->proxy['url'], $this->net);
                $node_port = $this->proxy['port'];
                break;
            case 'TORRENT':
                $node_url = \sprintf($this->torrent['url'], $this->net);
                $node_port = $this->torrent['port'];
                break;
            default:
                throw new \RuntimeException('Unknown node type. Type '.$node_type);
                break;
        }

        if ($node_url) {
            $list = \dns_get_record($node_url, DNS_A);
            $host_list = [];
            foreach ($list as $val) {
                switch ($node_type) {
                    case 'PROXY':
                        if ($res = $this->checkHost($val['ip'].':'.$node_port)) {
                            $host_list[$val['ip'].':'.$node_port] = 1;
                        }
                        break;
                    case 'TORRENT':
                        $host_list[$val['ip'].':'.$node_port] = 1;
                        break;
                    default:
                        throw new \RuntimeException('Unknown node type. Type '.$node_type);
                        break;
                }
            }

            $keys = \array_keys($host_list);
            if (\count($keys)) {
                $this->hosts[$node_type] = $keys[0];
                return $this->hosts[$node_type];
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
    private function checkHost(string $host): bool
    {
        if (! empty($host)) {
            $curl = $this->curl;
            \curl_setopt($curl, CURLOPT_URL, $host);
            \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
            \curl_setopt($curl, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
            \curl_setopt($curl, CURLOPT_POST, 1);
            \curl_setopt($curl, CURLOPT_POSTFIELDS, '{"id":"1","method":"","params":[]}');
            \curl_exec($curl);
            $code = \curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($code > 0 && $code < 500) {
                return true;
            }
        }

        return false;
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
    public function fetchHistory(string $address, int $beginTx = 1, int $countTx = self::HISTORY_LIMIT)
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
        if (! empty($host)) {
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

            if (isset($res['result']['count_blocks'])) {
                return (int)$res['result']['count_blocks'];
            }
        }

        return 0;
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
     * Make transaction signature
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360003271694-Creating-transactions
     *
     * @param string $address
     * @param string $value
     * @param string $nonce
     * @param int $fee
     * @param string $data
     *
     * @return bool|string
     */
    public function makeSign(string $address, string $value, string $nonce, int $fee = 0, string $data = '')
    {
        $a = (\strpos($address, '0x') === 0) ? \substr($address, 2) : $address;
        $b = IntHelper::VarUInt((int)$value, true);
        $c = IntHelper::VarUInt((int)$fee, true);
        $d = IntHelper::VarUInt((int)$nonce, true);

        $f = $data;
        $data_length = \strlen($f);
        $data_length = ($data_length > 0) ? $data_length / 2 : 0;
        $e = IntHelper::VarUInt((int)$data_length, true);

        $sign_text = $a.$b.$c.$d.$e.$f;

        return \hex2bin($sign_text);
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
        try {
            return $this->queryTorrent('get-block-by-number', ['number' => $number, 'type' => $type]);
        } catch (Exception $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
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
        try {
            return $this->queryTorrent('get-block-by-hash', ['hash' => $hash, 'type' => $type]);
        } catch (Exception $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
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
        try {
            $query = [
                'id'     => \time(),
                'method' => \trim($method),
                'params' => $data,
            ];

            $query = \json_encode($query);
            $url = $this->getConnectionAddress('PROXY');

            if ($url) {
                $curl = $this->curl;
                \curl_setopt($curl, CURLOPT_URL, $url);
                \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
                \curl_setopt($curl, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
                \curl_setopt($curl, CURLOPT_POST, 1);
                \curl_setopt($curl, CURLOPT_POSTFIELDS, $query);

                $result = \curl_exec($curl);

                return \json_decode($result, true);
            }
            throw new \RuntimeException('The proxy service is not available. Maybe you have problems with DNS.');
        } catch (Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
