# Metahash PHP
https://metahash.org

## Requirements

#### Basic requirements
- PHP 7.1+
- ext-gmp
- ext-curl
- composer

## Usage

```shell
git clone git@github.com:xboston/php-metahash.git
cd php-metahash
composer install --no-dev
php examples/cli.php method=generate
```

## More examples
```
php examples/cli.php method=generate
php examples/cli.php method=fetch-balance net=main address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833
php examples/cli.php method=fetch-history net=main address=0x00fa2a5279f8f0fd2f0f9d3280ad70403f01f9d62f52373833
php examples/cli.php method=get-tx net=main hash=bc4a521c1d0d958e2c00e9cdf90a66b15df918cd22e3c408b0f793d913fc7626
```

## Server mode
```
git clone git@github.com:xboston/php-metahash.git
cd php-metahash
composer install --no-dev
cd examples
php -S localhost:8000
```

open in browser: http://localhost:8000/ 

![](https://raw.githubusercontent.com/xboston/php-metahash/master/media/browser.png)


or http://localhost:8000/wallets.php

![](https://raw.githubusercontent.com/xboston/php-metahash/master/media/wallets.png)

## API
[Read more](https://github.com/metahashorg/crypt_example_php/wiki/API)
[Original](https://github.com/metahashorg/crypt_example_php)