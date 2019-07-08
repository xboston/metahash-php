<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Dump\Dump;
use Metahash\MetaHash;

$metaHash = new MetaHash();
$metaHash->setNetwork(MetaHash::NETWORK_MAIN);

$key = $metaHash->generateKey();
Dump::d('generateKey', $key);

// torrent nodes
$fetchBalance = $metaHash->fetchBalance('0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833');
Dump::d('fetchBalance', $fetchBalance);

$fetchBalances = $metaHash->fetchBalances([
    '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833',
    '0x0033626a3977271fd3d1c47e05e3f34c69f38661bdebacad65',
]);
Dump::d('fetchBalances', $fetchBalances);

$fetchHistory = $metaHash->fetchHistory('0x00312d149c348120faffe00ca275d012e2a64524979df899d3');
Dump::d('fetchHistory', $fetchHistory);

$fetchHistory10 = $metaHash->fetchHistory('0x00312d149c348120faffe00ca275d012e2a64524979df899d3', 10);
Dump::d('fetchHistory 10', $fetchHistory10);

$fetchHistory1010 = $metaHash->fetchHistory('0x00312d149c348120faffe00ca275d012e2a64524979df899d3', 10, 10);
Dump::d('fetchHistory 10 10', $fetchHistory1010);

$getTx = $metaHash->getTx('dd86635a7d7a8d8d44fc604f5fbb51eeb920dd28611b0f634e40b60af02a8c68');
Dump::d('getTx', $getTx);

$getBlockByHash = $metaHash->getBlockByHash('c59b4fd84827c5b836c46c51aacd5b70d640ef2d79e527d0d7ee5579253c197e', 2);
Dump::d('getBlockByHash', $getBlockByHash);

$getBlockByNumber = $metaHash->getBlockByNumber(1074984, 2);
Dump::d('getBlockByNumber', $getBlockByNumber);

$getLastTxs = $metaHash->getLastTxs();
Dump::d('getLastTxs', $getLastTxs);

$getLastTxs = $metaHash->getBlocks(10, 10);
Dump::d('getLastTxs', $getLastTxs);

$getDumpBlockByHash = $metaHash->getDumpBlockByHash('c59b4fd84827c5b836c46c51aacd5b70d640ef2d79e527d0d7ee5579253c197e', true);
Dump::d('getDumpBlockByHash', $getDumpBlockByHash);

$getDumpBlockByNumber = $metaHash->getDumpBlockByNumber(1074984, true);
Dump::d('getDumpBlockByNumber', $getDumpBlockByNumber);

$getCountBlocks = $metaHash->getCountBlocks();
Dump::d('getCountBlocks', $getCountBlocks);

$status = $metaHash->status();
Dump::d('status', $status);

// proxy nodes
$key = $metaHash->generateKey();
$sendTx = $metaHash->sendTx($key['private'], '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833', 1, 'https://github.com/xboston/php-metahash');
Dump::d('sendTx', $sendTx);

$getInfo = $metaHash->getInfo();
Dump::d('getInfo', $getInfo);

// extra methods
$getNonce = $metaHash->getNonce('0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833');
Dump::d('getNonce', $getNonce);
