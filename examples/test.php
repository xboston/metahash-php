<?php
require '/home/artshar/PhpstormProjects/php-metahash/vendor/autoload.php';

use Metahash\Crypto;
use Metahash\Ecdsa;

$crypto = new Crypto(new Ecdsa());
$crypto->net = 'main';
$balance = \json_encode($crypto->fetchLongHistory('0x00b18a4bdd748e05691e33f743e119f35d247bbc379e4ea9e9'), JSON_PRETTY_PRINT);

echo $balance;
