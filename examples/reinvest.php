<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use Metahash\MetaHash;

$youAddress = '0x00';
$youPrivateKey = '307...';

// invest node address
$investNodeAddress = '0x00...';

$metaHash = new MetaHash();
$nonce = $metaHash->getNonce($youAddress);

// 1 step - undelegate all
$delegations = $metaHash->getAddressDelegations($youAddress);
$commandUndelegate = '{"method":"undelegate"}';
foreach ($delegations['result']['states'] as $delegation) {
    $undelegateTx = $metaHash->sendTx($youPrivateKey, $delegation['to'], 0, $commandUndelegate, $nonce);
    \print_r(['undelegate' => $undelegateTx]);
}

// wait transaction commit
\sleep(3);

// 2 step - delegate all
$balanceResult = $metaHash->fetchBalance($youAddress)['result'];
$balance = $balanceResult['received'] - $balanceResult['spent'];

$commandDelegate = \sprintf('{"method":"delegate","params":{"value":"%d"}}', $balance);

$nonce += 1;
$delegateTx = $metaHash->sendTx($youPrivateKey, $investNodeAddress, 0, $commandDelegate, $nonce);
\print_r(['delegate' => $delegateTx]);
