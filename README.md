# Crypt example PHP
This repository contains PHP scripts that enable to generate MetaHash addresses, check its balance and see the full transaction history. Besides, `crypt_example.php` script describes methods allowing to create and send transactions as well as to obtain information on transaction via its hash. To learn more about all operations listed above please read the following articles: [Getting started with Metahash network](https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network), [Creating transactions](https://developers.metahash.org/hc/en-us/articles/360003271694-Creating-transactions) and [Operations with MetaHash address](https://developers.metahash.org/hc/en-us/articles/360008382213-Operations-with-MetaHash-address). 

There are 2 ways of working with the script:

1) Using the extension for php `mhcrypto` [Read more](https://github.com/metahashorg/crypt_example_php/wiki/Using-the-extension-for-php)
2) Using the php library of `mdanter/ecc` [Read more](https://github.com/metahashorg/crypt_example_php/wiki/Using-the-php-library)

You can use the path that suits you.

## Requirements

#### Basic requirements
- PHP 7.1+
- ext-gmp
- ext-curl

#### Additional requirements for `mhcrypto` extension

- ext-mhcrypto (see [https://github.com/metahashorg/php-mhcrypto](https://github.com/metahashorg/php-mhcrypto))

#### Additional requirements for `mdanter/ecc` library

- composer
- mdanter/ecc (0.5.0)


## Usage

```shell
php crypt_example.php [params]
```

Repository contains demo page `index.html`. In order to work with this demo page you need to have an installed web server.

## API

[Read more](https://github.com/metahashorg/crypt_example_php/wiki/API)
