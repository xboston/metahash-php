<?php declare(strict_types=1);

namespace Metahash;

class IntHelper
{
    public static function Int8($i, $hex = false)
    {
        return \is_int($i) ? self::Pack('c', $i, $hex) : self::UnPack('c', $i, $hex)[1];
    }

    private static function Pack($mode, $i, $hex = false)
    {
        return $hex ? \bin2hex(\pack($mode, $i)) : \pack($mode, $i);
    }

    private static function UnPack($mode, $i, $hex = false)
    {
        return $hex ? \unpack($mode, \hex2bin($i)) : \unpack($mode, $i);
    }

    public static function Int16($i, $hex = false)
    {
        return \is_int($i) ? self::Pack('s', $i, $hex) : self::UnPack('s', $i, $hex)[1];
    }

    public static function Int32($i, $hex = false)
    {
        return \is_int($i) ? self::Pack('l', $i, $hex) : self::UnPack('l', $i, $hex)[1];
    }

    public static function Int64($i, $hex = false)
    {
        return \is_int($i) ? self::Pack('q', $i, $hex) : self::UnPack('q', $i, $hex)[1];
    }

    public static function VarUInt($i, $hex = false)
    {
        if (\is_int($i)) {
            if ($i < 250) {
                return self::UInt8($i, $hex);
            } elseif ($i < 65536) {
                return self::UInt8(250, $hex).self::UInt16($i, $hex);
            } elseif ($i < 4294967296) {
                return self::UInt8(251, $hex).self::UInt32($i, $hex);
            } else {
                return self::UInt8(252, $hex).self::UInt64($i, $hex);
            }
        } else {
            $l = \strlen($i);
            if ($l == 2) {
                return self::UInt8($i, $hex);
            } elseif ($l == 4) {
                return self::UInt16($i, $hex);
            } elseif ($l == 6) {
                return self::UInt16(\substr($i, 2), $hex);
            } elseif ($l == 8) {
                return self::UInt32($i, $hex);
            } elseif ($l == 10) {
                return self::UInt32(\substr($i, 2), $hex);
            } elseif ($l == 18) {
                return self::UInt64(\substr($i, 2), $hex);
            } else {
                return self::UInt64($i, $hex);
            }
        }
    }

    public static function UInt8($i, $hex = false)
    {
        return \is_int($i) ? self::Pack('C', $i, $hex) : self::UnPack('C', $i, $hex)[1];
    }

    public static function UInt16($i, $hex = false, $endianness = false)
    {
        $f = \is_int($i) ? 'Pack' : 'UnPack';

        if ($endianness === true) { // big-endian
            $i = self::$f('n', $i, $hex);
        } elseif ($endianness === false) { // little-endian
            $i = self::$f('v', $i, $hex);
        } elseif ($endianness === null) { // machine byte order
            $i = self::$f('S', $i, $hex);
        }

        return \is_array($i) ? $i[1] : $i;
    }

    public static function UInt32($i, $hex = false, $endianness = false)
    {
        $f = \is_int($i) ? 'Pack' : 'UnPack';

        if ($endianness === true) { // big-endian
            $i = self::$f('N', $i, $hex);
        } else {
            if ($endianness === false) { // little-endian
                $i = self::$f('V', $i, $hex);
            } else {
                if ($endianness === null) { // machine byte order
                    $i = self::$f('L', $i, $hex);
                }
            }
        }

        return \is_array($i) ? $i[1] : $i;
    }

    public static function UInt64($i, $hex = false, $endianness = false)
    {
        $f = \is_int($i) ? 'Pack' : 'UnPack';

        if ($endianness === true) { // big-endian
            $i = self::$f('J', $i, $hex);
        } else {
            if ($endianness === false) { // little-endian
                $i = self::$f('P', $i, $hex);
            } else {
                if ($endianness === null) { // machine byte order
                    $i = self::$f('Q', $i, $hex);
                }
            }
        }

        return \is_array($i) ? $i[1] : $i;
    }
}
