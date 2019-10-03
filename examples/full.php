<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Dump\Dump;
use Metahash\HistoryFilters;
use Metahash\MetaHash;

$metaHash = new MetaHash();
$metaHash->setNetwork(MetaHash::NETWORK_MAIN);

Dump::d('generateKey', $metaHash->generateKey());

// torrent nodes
Dump::d('fetch-balance', $metaHash->fetchBalance('0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833'));

$fetchBalances = $metaHash->fetchBalances([
    '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833',
    '0x0033626a3977271fd3d1c47e05e3f34c69f38661bdebacad65',
]);
Dump::d('fetch-balances', $fetchBalances);

Dump::d('fetch-history', $metaHash->fetchHistory('0x00312d149c348120faffe00ca275d012e2a64524979df899d3'));

Dump::d('fetch-history 10', $metaHash->fetchHistory('0x00312d149c348120faffe00ca275d012e2a64524979df899d3', 10));

Dump::d('fetch-history 10 10', $metaHash->fetchHistory('0x00312d149c348120faffe00ca275d012e2a64524979df899d3', 10, 10));

$filter = new HistoryFilters();
$filter->setIsForging(true);
$fetchHistoryFilter = $metaHash->fetchHistoryFilter('0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833', $filter, 2);
Dump::d('fetch-history-filter', $fetchHistoryFilter);

Dump::d('get-tx', $metaHash->getTx('dd86635a7d7a8d8d44fc604f5fbb51eeb920dd28611b0f634e40b60af02a8c68'));

Dump::d('get-block-by-hash', $metaHash->getBlockByHash('c59b4fd84827c5b836c46c51aacd5b70d640ef2d79e527d0d7ee5579253c197e', 2));

Dump::d('get-block-by-number', $metaHash->getBlockByNumber(1074984, 2));

Dump::d('get-last-txs', $metaHash->getLastTxs());

Dump::d('get-last-txs', $metaHash->getBlocks(10, 10));

Dump::d('get-dump-block-by-hash', $metaHash->getDumpBlockByHash('c59b4fd84827c5b836c46c51aacd5b70d640ef2d79e527d0d7ee5579253c197e', true));

Dump::d('get-dump-block-by-number', $metaHash->getDumpBlockByNumber(1074984, true));

Dump::d('get-count-blocks', $metaHash->getCountBlocks());

Dump::d('get-forging-sum', $metaHash->getForgingSum(1));

Dump::d('get-last-node-stat-result', $metaHash->getLastNodeStatResult('0x00d5b768fee94349103e2f69484dff207a3bbb2a5077defd6e'));

Dump::d('get-last-node-stat-trust', $metaHash->getLastNodeStatTrust('0x00d5b768fee94349103e2f69484dff207a3bbb2a5077defd6e'));

Dump::d('get-last-node-stat-count', $metaHash->getLastNodeStatCount('0x00d5b768fee94349103e2f69484dff207a3bbb2a5077defd6e'));

Dump::d('get-last-nodes-stats-count', $metaHash->getLastNodesStatsCount());

Dump::d('get-all-last-nodes-count', $metaHash->getAllLastNodesCount(2));

Dump::d('get-nodes-raiting', $metaHash->getNodesRating('0x00d5b768fee94349103e2f69484dff207a3bbb2a5077defd6e', 2));

Dump::d('get-common-balance', $metaHash->getCommonBalance());

Dump::d('status', $metaHash->status());

// proxy nodes
$key = $metaHash->generateKey();
$sendTx = $metaHash->sendTx($key['private'], '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833', 0, 'https://github.com/xboston/php-metahash');
Dump::d('mhc_send', $sendTx);

$getInfo = $metaHash->getInfo();
Dump::d('getinfo', $getInfo);

// extra methods
$getNonce = $metaHash->getNonce('0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833');
Dump::d('getNonce', $getNonce);
