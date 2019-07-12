<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Dump\Dump;
use Metahash\MetaHash;

$youAddress ='0x00e327ebc4691ae115a7146384732308d8bc11280e3922aa44';
$youPrivateKey = '30740201010420f882269a823c7a1721a0b0b1b7c2de2f9c13a744ef7c8b7dd3a95a09b421b277a00706052b8104000aa14403420004ac0925d33c19e35f2025c4738dba3b32e046e9f0d83930f7c6539fc9975adedcef18123797d7f99778e7cdd801996f88058a8e8fb1cfeadadb1bffd049907250';



$metaHash = new MetaHash();
$metaHash->setNetwork(MetaHash::NETWORK_MAIN);

// command undelegate
$command = '{"method":"undelegate"}';
$nonce = $metaHash->getNonce($youAddress);
$sendTx = $metaHash->sendTx($youPrivateKey, '0x0073c4f389dc330d21e8e24398d7ee9fea93c01a7e9344fbcd', 0, $command, $nonce);

Dump::d('sendTx', $sendTx);
