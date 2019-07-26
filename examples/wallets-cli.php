<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Metahash\MetaHash;

$crypto = new MetaHash();

$wallets = [
    '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833' => 'MetaWat.ch',
    '0x00333170c3c1d908a1c4918ef3cff4a831eb69581adb42e9ce' => 'MHAG-iTorrent-03',
    '0x00760dd55ca9a10fcef7ae4f71ef53356ba8f2bd0fa0efe911' => 'Wallet ICO',
];

$balances = $crypto->fetchBalances(\array_keys($wallets));

$results = [];
$fullBalance = 0;
$fullDelegated = 0;

foreach ($balances['result'] as $balanceData) {
    $balance = $balanceData['received'] - $balanceData['spent'];
    $delegated = isset($balanceData['delegate']) ? $balanceData['delegate'] - $balanceData['undelegate'] : 0;

    $results[] = [
        'address'   => $balanceData['address'],
        'balance'   => $balance / 1e6,
        'delegated' => $delegated / 1e6,
    ];
}

\print_r($results);