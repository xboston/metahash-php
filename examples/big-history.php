<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Metahash\MetaHash;

$metaHash = new MetaHash();

// big history node
$address = '0x0038b12b0fafdc0ec523e3059882daf56fc3b3f6932a527987';

$bigHistoryResult = fetchFullHistory($metaHash, $address);
\print_r($bigHistoryResult);

function fetchFullHistory(MetaHash $metaHash, string $address)
{
    $maxLimit = MetaHash::HISTORY_LIMIT;

    $balance = $metaHash->fetchBalance($address);
    if ($balance['result']['count_txs'] <= $maxLimit) {
        return $metaHash->fetchHistory($address, $maxLimit);
    }

    $pages = \ceil($balance['result']['count_txs'] / $maxLimit) - 1;

    $options = [[]];
    for ($index = 0; $index <= $pages; $index++) {
        $history = $metaHash->fetchHistory($address, $maxLimit, $index * $maxLimit);
        $options[] = $history['result'];
    }

    $result = $result = [
        'id'     => 1,
        'result' => \array_merge(...$options),
    ];

    return $result;
}
