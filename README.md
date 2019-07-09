# MetaHash API PHP library

An unofficial PHP library for [#MetaHash](https://metahash.org ) blockchain.

### Requirements

- PHP 7.1+
- ext-gmp
- ext-curl
- composer

### Installation
You can install this package with Composer. You only need to require xboston/metahash.

```bash
composer require xboston/metahash
```

### Information

- [Missing #MetaHash API documentation](https://github.com/xboston/metahash-api)
- [Original source](https://github.com/metahashorg/crypt_example_php)
- [Knowledge base](https://developers.metahash.org)
- [Testpage portal](http://testpage.metahash.org/)

### Methods

- [x] fetch-balance
- [x] fetch-balances
- [x] fetch-history
- [x] get-tx
- [x] get-block-by-hash
- [x] get-block-by-number
- [x] get-last-txs
- [x] get-blocks
- [x] get-dump-block-by-number
- [x] get-dump-block-by-hash
- [x] get-count-blocks
- [x] status
- [x] mhc_send
- [x] getinfo


### Extra Methods
- [x] [generateKey](https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network)
- [x] getNonce

### Usage
You can find usage examples in the [examples](https://github.com/xboston/php-metahash/examples) folder.

### Examples
```php
<?php

use Metahash\MetaHash;

$metaHash = new MetaHash();
$balance = $metaHash->fetchBalance('0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833');
print_r($balance);
```

### Console examples
```shell
git clone git@github.com:xboston/php-metahash.git
cd php-metahash
composer install --no-dev
php examples/cli.php method=generate
php examples/cli.php method=fetch-balance address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833
php examples/cli.php method=fetch-balances address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833,0x0039f42ad734606d250ea0b0151d4aeab6b4edc6587c4b27ef
php examples/cli.php method=fetch-history address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833
php examples/cli.php method=get-tx  hash=bc4a521c1d0d958e2c00e9cdf90a66b15df918cd22e3c408b0f793d913fc7626
php examples/cli.php method=get-last-txs
```

### Server mode examples

```
git clone git@github.com:xboston/php-metahash.git
cd php-metahash
composer install --no-dev
cd examples
php -S localhost:8000
```

open in browser: http://localhost:8000/ 

![browser](https://raw.githubusercontent.com/xboston/php-metahash/master/media/browser.png)


or http://localhost:8000/wallets.php

![wallets](https://raw.githubusercontent.com/xboston/php-metahash/master/media/wallets.png)


## Breaking change

- from v0.1.1 to v0.2.0
```diff
-public function fetchHistory(string $address, int $beginTx = 0, int $countTx = self::HISTORY_LIMIT)
+public function fetchHistory(string $address, int $countTx = self::HISTORY_LIMIT, int $beginTx = 0)

-public function sendTx(string $to, string $value, string $fee = '', int $nonce = 1, string $data = '', string $key = '', string $sign = '')
+public function sendTx(string $privateKey, string $to, int $value, string $data = '', int $nonce = 1, int $fee = 0)

-public function makeSign(string $address, string $value, string $nonce, int $fee = 0, string $data = '')
+public function makeSign(string $address, int $value, int $nonce, int $fee = 0, string $data = '')
```

## License

This package is released under the MIT license.
