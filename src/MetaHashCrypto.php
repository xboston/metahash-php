<?php declare(strict_types=1);

namespace Metahash;

use FG\ASN1\Exception\ParserException;
use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\Crypto\Signature\SignHasher;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\GmpMathInterface;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Random\RandomGeneratorFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\Signature\DerSignatureSerializer;

/**
 * Class MetaHashCrypto
 *
 * @package Metahash
 */
class MetaHashCrypto
{
    /**
     * @var GmpMathInterface
     */
    private $adapter;
    /**
     * @var GeneratorPoint
     */
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
        $serializerPrivate = new DerPrivateKeySerializer($this->adapter);
        $dataPrivate = $serializerPrivate->serialize($private);
        $result['private'] = '0x'.\bin2hex($dataPrivate);

        $public = $private->getPublicKey();
        $serializerPublic = new DerPublicKeySerializer($this->adapter);
        $dataPublic = $serializerPublic->serialize($public);
        $result['public'] = '0x'.\bin2hex($dataPublic);

        $result['address'] = $this->getAdress($result['public']);

        return $result;
    }

    /**
     * Creating a Metahash address
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network
     *
     * @param string $keyPublic
     * @param string $net
     *
     * @return string
     */
    public function getAdress(string $keyPublic, string $net = '00'): string
    {
        $address = null;

        $serializerPublic = new DerPublicKeySerializer($this->adapter);
        $keyPublic = $this->parseBase16($keyPublic);
        $keyPublic = \hex2bin($keyPublic);
        $keyPublic = $serializerPublic->parse($keyPublic);
        $x = \gmp_strval($keyPublic->getPoint()->getX(), 16);
        $xlen = 64 - \strlen($x);
        $x = ($xlen > 0) ? \str_repeat('0', $xlen).$x : $x;
        $y = \gmp_strval($keyPublic->getPoint()->getY(), 16);
        $ylen = 64 - \strlen($y);
        $y = ($ylen > 0) ? \str_repeat('0', $ylen).$y : $y;

        $code = '04'.$x.$y;
        $code = \hex2bin($code);
        $code = \hex2bin(\hash('sha256', $code));
        $code = $net.\hash('ripemd160', $code);
        $code = \hex2bin($code);
        $hashSumm = \hex2bin(\hash('sha256', $code));
        $hashSumm = \hash('sha256', $hashSumm);
        $hashSumm = \substr($hashSumm, 0, 8);
        $address = \bin2hex($code).$hashSumm;

        return $this->toBase16($address);
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
     * @param string $string
     *
     * @return string
     */
    public function toBase16(string $string): string
    {
        return (\strpos($string, '0x') === 0) ? $string : '0x'.$string;
    }

    /**
     * Generating a public key
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360002712193-Getting-started-with-Metahash-network
     *
     * @param string $privateKey
     *
     * @return string
     * @throws ParserException
     */
    public function privateToPublic(string $privateKey): string
    {
        $serializerPrivate = new DerPrivateKeySerializer($this->adapter);
        $privateKey = $this->parseBase16($privateKey);
        $privateKey = \hex2bin($privateKey);
        $key = $serializerPrivate->parse($privateKey);

        $public = $key->getPublicKey();
        $serializerPublic = new DerPublicKeySerializer($this->adapter);
        $dataPublic = $serializerPublic->serialize($public);

        return '0x'.\bin2hex($dataPublic);
    }

    /**
     * Signature data
     *
     * @param string $data
     * @param string $privateKey
     * @param bool $rand
     * @param string $algo
     *
     * @return string
     * @throws ParserException
     */
    public function sign(string $data, string $privateKey, $rand = false, $algo = 'sha256'): string
    {
        $serializerPrivate = new DerPrivateKeySerializer($this->adapter);
        $privateKey = $this->parseBase16($privateKey);
        $privateKey = \hex2bin($privateKey);
        $key = $serializerPrivate->parse($privateKey);

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
     * @param string $publicKey
     * @param string $algo
     *
     * @return bool
     * @throws ParserException
     */
    public function verify(string $sign, string $data, string $publicKey, string $algo = 'sha256'): bool
    {
        $serializer = new DerSignatureSerializer();
        $serializerPublic = new DerPublicKeySerializer($this->adapter);

        $publicKey = $this->parseBase16($publicKey);
        $publicKey = \hex2bin($publicKey);
        $key = $serializerPublic->parse($publicKey);

        $hasher = new SignHasher($algo);
        $hash = $hasher->makeHash($data, $this->generator);

        $sign = $this->parseBase16($sign);
        $sign = \hex2bin($sign);
        $serializedSign = $serializer->parse($sign);
        $signer = new Signer($this->adapter);

        return $signer->verify($key, $serializedSign, $hash) ? true : false;
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

        $addressHashSumm = \substr($address, \strlen($address) - 8, 8);
        $code = \substr($address, 0, \strlen($address) - 8);
        $code = \substr($code, 2);
        $code = \hex2bin($code);
        $hashSumm = \hex2bin(\hash('sha256', $code));
        $hashSumm = \hash('sha256', $hashSumm);
        $hashSumm = \substr($hashSumm, 0, 8);

        return $addressHashSumm === $hashSumm;
    }


    /**
     * Make transaction signature
     *
     * @see https://developers.metahash.org/hc/en-us/articles/360003271694-Creating-transactions
     *
     * @param string $address
     * @param string $value
     * @param string $nonce
     * @param int $fee
     * @param string $data
     *
     * @return bool|string
     */
    public function makeSign(string $address, string $value, string $nonce, int $fee = 0, string $data = '')
    {
        $a = (\strpos($address, '0x') === 0) ? \substr($address, 2) : $address;
        $b = IntHelper::VarUInt((int)$value, true);
        $c = IntHelper::VarUInt((int)$fee, true);
        $d = IntHelper::VarUInt((int)$nonce, true);

        $f = $data;
        $data_length = \strlen($f);
        $data_length = ($data_length > 0) ? $data_length / 2 : 0;
        $e = IntHelper::VarUInt((int)$data_length, true);

        $sign_text = $a.$b.$c.$d.$e.$f;

        return \hex2bin($sign_text);
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

    /**
     * @return GmpMathInterface
     */
    public function getAdapter(): GmpMathInterface
    {
        return $this->adapter;
    }

    /**
     * @param GmpMathInterface $adapter
     */
    public function setAdapter(GmpMathInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * @return GeneratorPoint
     */
    public function getGenerator(): GeneratorPoint
    {
        return $this->generator;
    }

    /**
     * @param GeneratorPoint $generator
     */
    public function setGenerator(GeneratorPoint $generator): void
    {
        $this->generator = $generator;
    }
}
