<?php declare(strict_types=1);

namespace Metahash;

use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\Crypto\Signature\SignHasher;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Random\RandomGeneratorFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\Signature\DerSignatureSerializer;

class Ecdsa
{
    private $adapter;
    private $generator;

    public function __construct()
    {
        $this->adapter = EccFactory::getAdapter();
        $this->generator = EccFactory::getSecgCurves()->generator256r1();
    }

    public function getKey(): array
    {
        $result = [
            'private' => null,
            'public'  => null,
            'address' => null,
        ];

        $private = $this->generator->createPrivateKey();
        $serializer_private = new DerPrivateKeySerializer($this->adapter);
        $data_private = $serializer_private->serialize($private);
        $result['private'] = '0x' . \bin2hex($data_private);

        $public = $private->getPublicKey();
        $serializer_public = new DerPublicKeySerializer($this->adapter);
        $data_public = $serializer_public->serialize($public);
        $result['public'] = '0x' . \bin2hex($data_public);


        return $result;
    }

    public function privateToPublic($private_key): ?string
    {
        $serializer_private = new DerPrivateKeySerializer($this->adapter);
        $private_key = $this->parseBase16($private_key);
        $private_key = \hex2bin($private_key);
        $key = $serializer_private->parse($private_key);

        $public = $key->getPublicKey();
        $serializer_public = new DerPublicKeySerializer($this->adapter);
        $data_public = $serializer_public->serialize($public);

        return '0x' . \bin2hex($data_public);
    }

    public function parseBase16($string)
    {
        return (\substr($string, 0, 2) === '0x') ? \substr($string, 2) : $string;
    }

    public function sign($data, $private_key, $rand = false, $algo = 'sha256'): string
    {
        $sign = null;


        $serializer_private = new DerPrivateKeySerializer($this->adapter);
        $private_key = $this->parseBase16($private_key);
        $private_key = \hex2bin($private_key);
        $key = $serializer_private->parse($private_key);

        $hasher = new SignHasher($algo, $this->adapter);
        $hash = $hasher->makeHash($data, $this->generator);

        if (!$rand) {
            $random = RandomGeneratorFactory::getHmacRandomGenerator($key, $hash, $algo);
        } else {
            $random = RandomGeneratorFactory::getRandomGenerator();
        }

        $randomK = $random->generate($this->generator->getOrder());
        $signer = new Signer($this->adapter);
        $signature = $signer->sign($key, $hash, $randomK);

        $serializer = new DerSignatureSerializer();
        $sign = $serializer->serialize($signature);


        return '0x' . \bin2hex($sign);
    }

    public function verify($sign, $data, $public_key, $algo = 'sha256'): bool
    {
        $serializer = new DerSignatureSerializer();
        $serializer_public = new DerPublicKeySerializer($this->adapter);

        $public_key = $this->parseBase16($public_key);
        $public_key = \hex2bin($public_key);
        $key = $serializer_public->parse($public_key);

        $hasher = new SignHasher($algo);
        $hash = $hasher->makeHash($data, $this->generator);

        $sign = $this->parseBase16($sign);
        $sign = \hex2bin($sign);
        $serialized_sign = $serializer->parse($sign);
        $signer = new Signer($this->adapter);

        return $signer->verify($key, $serialized_sign, $hash) ? true : false;
    }

    public function getAdress($key, $net = '00'): string
    {
        $address = null;


        $serializer_public = new DerPublicKeySerializer($this->adapter);
        $key = $this->parseBase16($key);
        $key = \hex2bin($key);
        $key = $serializer_public->parse($key);
        $x = \gmp_strval($key->getPoint()->getX(), 16);
        $xlen = 64 - \strlen($x);
        $x = ($xlen > 0) ? \str_repeat('0', $xlen) . $x : $x;
        $y = \gmp_strval($key->getPoint()->getY(), 16);
        $ylen = 64 - \strlen($y);
        $y = ($ylen > 0) ? \str_repeat('0', $ylen) . $y : $y;

        $code = '04' . $x . $y;
        $code = \hex2bin($code);
        $code = \hex2bin(\hash('sha256', $code));
        $code = $net . \hash('ripemd160', $code);
        $code = \hex2bin($code);
        $hash_summ = \hex2bin(\hash('sha256', $code));
        $hash_summ = \hash('sha256', $hash_summ);
        $hash_summ = \substr($hash_summ, 0, 8);
        $address = \bin2hex($code) . $hash_summ;


        return $this->toBase16($address);
    }

    public function toBase16($string)
    {
        return (\strpos($string, '0x') === 0) ? $string : '0x' . $string;
    }

    public function checkAdress(string $address): bool
    {
        if (!empty($address)) {
            if (\strlen($this->parseBase16($address)) % 2) {
                return false;
            }

            $address_hash_summ = \substr($address, \strlen($address) - 8, 8);
            $code = \substr($address, 0, \strlen($address) - 8);
            $code = \substr($code, 2);
            $code = \hex2bin($code);
            $hash_summ = \hex2bin(\hash('sha256', $code));
            $hash_summ = \hash('sha256', $hash_summ);
            $hash_summ = \substr($hash_summ, 0, 8);

            if ($address_hash_summ === $hash_summ) {
                return true;
            }
        }

        return false;
    }
}
