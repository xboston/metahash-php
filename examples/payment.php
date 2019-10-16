<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Dump\Dump;
use Metahash\MetaHash;

$metaHash = new MetaHash();
$metaHash->setNetwork(MetaHash::NETWORK_MAIN);


$fromPrivateKey = '30740201010420f882269a823c7a1721a0b0b1b7c2de2f9c13a744ef7c8b7dd3a95a09b421b277a00706052b8104000aa14403420004ac0925d33c19e35f2025c4738dba3b32e046e9f0d83930f7c6539fc9975adedcef18123797d7f99778e7cdd801996f88058a8e8fb1cfeadadb1bffd049907250';


$fromAddress ='0x00e327ebc4691ae115a7146384732308d8bc11280e3922aa44';
$toAddress = '0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833';
$amount = 0;
$data = 'send via https://github.com/xboston/php-metahash';

$nonce = $metaHash->getNonce($fromAddress);
$sendTx = $metaHash->sendTx($fromPrivateKey, $toAddress, $amount, $data, $nonce);

Dump::d($sendTx);
