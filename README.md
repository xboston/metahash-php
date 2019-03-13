# Metahash PHP

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
php examples/cli.php method=fetch-balance net=dev address=0x003da54f19ee81d86c0d6d40514b25efb701533e9f8e233fdc
php examples/cli.php method=fetch-history net=dev address=0x003da54f19ee81d86c0d6d40514b25efb701533e9f8e233fdc
```

## Missing methods
```
create-tx
```

## API

[Read more](https://github.com/metahashorg/crypt_example_php/wiki/API)
