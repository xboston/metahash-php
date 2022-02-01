# MetaHash API PHP library

An unofficial PHP library for [#MetaHash](https://metahash.org ) blockchain.

### Requirements

- PHP 8.0.2+
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
- [x] get-address-delegations
- [x] get-tx
- [x] get-block-by-hash
- [x] get-block-by-number
- [x] get-last-txs
- [x] get-blocks
- [x] get-dump-block-by-number
- [x] get-dump-block-by-hash
- [x] get-count-blocks
- [x] get-forging-sum
- [x] get-last-node-stat-result
- [x] get-last-node-stat-trust
- [x] get-last-node-stat-count
- [x] get-last-nodes-stats-count
- [x] get-all-last-nodes-count
- [x] get-nodes-raiting
- [x] get-common-balance
- [x] status
- [x] mhc_send
- [x] getinfo


### Extra Methods
- [x] [generateKey](https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network)
- [x] getNonce

### Usage
You can find usage examples in the [examples](https://github.com/xboston/metahash-php/tree/master/examples) folder.

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
git clone git@github.com:xboston/metahash-php.git
cd metahash-php
composer install
php examples/cli.php method=generate
php examples/cli.php method=fetch-balance address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833
php examples/cli.php method=fetch-balances address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833,0x0039f42ad734606d250ea0b0151d4aeab6b4edc6587c4b27ef
php examples/cli.php method=fetch-history address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833
php examples/cli.php method=get-tx  hash=bc4a521c1d0d958e2c00e9cdf90a66b15df918cd22e3c408b0f793d913fc7626
php examples/cli.php method=get-last-txs
```

### Server mode examples

```
git clone git@github.com:xboston/metahash-php.git
cd metahash-php
composer install
cd examples
php -S localhost:8000
```

open in browser: http://localhost:8000/ 

![browser](https://raw.githubusercontent.com/xboston/metahash-php/master/media/browser.png)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fxboston%2Fmetahash-php.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fxboston%2Fmetahash-php?ref=badge_shield)


or http://localhost:8000/wallets.php

![wallets](https://raw.githubusercontent.com/xboston/metahash-php/master/media/wallets.png)


## License

This package is released under the MIT license.


[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fxboston%2Fmetahash-php.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fxboston%2Fmetahash-php?ref=badge_large)