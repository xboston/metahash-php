<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Metahash\MetaHash;

$metaHash = new MetaHash();

\var_dump('secp256k1', $metaHash->generateKey(MetaHash::KEY_TYPE_SECP256K1));
\var_dump('secp256r1', $metaHash->generateKey(MetaHash::KEY_TYPE_SECP256R1));
