<?php declare(strict_types=1);

namespace Metahash;

use FG\ASN1\Exception\ParserException;
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

    /**
     * Ecdsa constructor.
     */
    public function __construct()
    {
        $this->adapter = EccFactory::getAdapter();
        $this->generator = EccFactory::getSecgCurves()->generator256r1();
    }

    /**
     * Metahash key generate
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network
     *
     * @return array
     */
    public function generateKey(): array
    {
        $result = [
            'private' => null,
            'public'  => null,
            'address' => null,
        ];

        $private = $this->generator->createPrivateKey();
        $serializer_private = new DerPrivateKeySerializer($this->adapter);
        $data_private = $serializer_private->serialize($private);
        $result['private'] = '0x'.\bin2hex($data_private);

        $public = $private->getPublicKey();
        $serializer_public = new DerPublicKeySerializer($this->adapter);
        $data_public = $serializer_public->serialize($public);
        $result['public'] = '0x'.\bin2hex($data_public);

        return $result;
    }

    /**
     * Generating a public key
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network
     *
     * @param string $private_key
     *
     * @return string
     * @throws ParserException
     */
    public function privateToPublic(string $private_key): string
    {
        $serializer_private = new DerPrivateKeySerializer($this->adapter);
        $private_key = $this->parseBase16($private_key);
        $private_key = \hex2bin($private_key);
        $key = $serializer_private->parse($private_key);

        $public = $key->getPublicKey();
        $serializer_public = new DerPublicKeySerializer($this->adapter);
        $data_public = $serializer_public->serialize($public);

        return '0x'.\bin2hex($data_public);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function parseBase16(string $string): string
    {
        return (\strpos($string, '0x') === 0) ? \substr($string, 2) : $string;
    }

    /**
     * Signature data
     *
     * @param string $data
     * @param string $private_key
     * @param bool $rand
     * @param string $algo
     *
     * @return string
     * @throws ParserException
     */
    public function sign(string $data, string $private_key, $rand = false, $algo = 'sha256'): string
    {
        $sign = null;

        $serializer_private = new DerPrivateKeySerializer($this->adapter);
        $private_key = $this->parseBase16($private_key);
        $private_key = \hex2bin($private_key);
        $key = $serializer_private->parse($private_key);

        $hasher = new SignHasher($algo, $this->adapter);
        $hash = $hasher->makeHash($data, $this->generator);

        if (! $rand) {
            $random = RandomGeneratorFactory::getHmacRandomGenerator($key, $hash, $algo);
        } else {
            $random = RandomGeneratorFactory::getRandomGenerator();
        }

        $randomK = $random->generate($this->generator->getOrder());
        $signer = new Signer($this->adapter);
        $signature = $signer->sign($key, $hash, $randomK);

        $serializer = new DerSignatureSerializer();
        $sign = $serializer->serialize($signature);


        return '0x'.\bin2hex($sign);
    }

    /**
     * Verify signed data
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network
     *
     * @param string $sign
     * @param string $data
     * @param string $public_key
     * @param string $algo
     *
     * @return bool
     * @throws ParserException
     */
    public function verify(string $sign, string $data, string $public_key, string $algo = 'sha256'): bool
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

    /**
     * Creating a Metahash address
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network
     *
     * @param string $key
     * @param string $net
     *
     * @return string
     */
    public function getAdress(string $key, string $net = '00'): string
    {
        $address = null;

        $serializer_public = new DerPublicKeySerializer($this->adapter);
        $key = $this->parseBase16($key);
        $key = \hex2bin($key);
        $key = $serializer_public->parse($key);
        $x = \gmp_strval($key->getPoint()->getX(), 16);
        $xlen = 64 - \strlen($x);
        $x = ($xlen > 0) ? \str_repeat('0', $xlen).$x : $x;
        $y = \gmp_strval($key->getPoint()->getY(), 16);
        $ylen = 64 - \strlen($y);
        $y = ($ylen > 0) ? \str_repeat('0', $ylen).$y : $y;

        $code = '04'.$x.$y;
        $code = \hex2bin($code);
        $code = \hex2bin(\hash('sha256', $code));
        $code = $net.\hash('ripemd160', $code);
        $code = \hex2bin($code);
        $hash_summ = \hex2bin(\hash('sha256', $code));
        $hash_summ = \hash('sha256', $hash_summ);
        $hash_summ = \substr($hash_summ, 0, 8);
        $address = \bin2hex($code).$hash_summ;

        return $this->toBase16($address);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function toBase16(string $string): string
    {
        return (\strpos($string, '0x') === 0) ? $string : '0x'.$string;
    }

    /**
     * Validate address
     *
     * @param string $address
     *
     * @return bool
     */
    public function checkAdress(string $address): bool
    {
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

        return $address_hash_summ === $hash_summ;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function str2hex(string $string): string
    {
        return \implode(\unpack('H*', $string));
    }

    /**
     * @param string $hex
     *
     * @return string
     */
    public function hex2str(string $hex): string
    {
        return \pack('H*', $hex);
    }
}
