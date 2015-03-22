<?php

namespace Intaro\HStore;

final class Coder
{
    public static function encode(array $arr)
    {
        static $escape = '"\\';

        if (!$arr) {
            return '';
        }

        $result = '';

        foreach ($arr as $key => $value) {
            if (isset($result[0])) {
                $result .= ', ';
            }

            if (null === $value) {
                $result .= '"' . addcslashes($key, $escape) . '"=>NULL';
            } else {
                $result .= '"' . addcslashes($key, $escape) . '"=>"' . addcslashes($value, $escape) . '"';
            }
        }

        return $result;
    }

    public static function decode($str)
    {
        static $spaces = " \t\r\n";
        static $sp_key = "= \t\r\n";
        static $sp_val = ", \t\r\n";
        static $regexp = '/" ((?>[^"\\\\]+|\\\\.)*) "/Asx';

        $len = strlen($str);

        // skip spaces from right
        while ($len > 0 && false !== strpos($spaces, $str[$len - 1])) {
            --$len;
        }

        if (0 === $len) {
            return [];
        }

        $p = strspn($str, $spaces);

        $result = array();
        $key    = null;
        $value  = null;
        $state  = 0;

        while ($p < $len) {
            if (0 === $state) {
                if ('"' === $str[$p]) {
                    if (preg_match($regexp, $str, $m, 0, $p)) {
                        $key = stripcslashes($m[1]);
                        $p  += strlen($m[0]);
                    } else {
                        throw new \RuntimeException(sprintf("Syntax error at %p", $p));
                    }
                } else {
                    $key = self::read($str, $sp_key, $p);
                }

                $state = 1;
            } elseif (1 === $state) {
                if ($p !== strpos($str, '=>', $p)) {
                    throw new \RuntimeException(sprintf("Syntax error at %p", $p));
                }

                $p    += 2;
                $state = 2;
            } elseif (2 === $state) {
                if ('"' === $str[$p]) {
                    if (preg_match($regexp, $str, $m, 0, $p)) {
                        $value = stripcslashes($m[1]);
                        $p    += strlen($m[0]);
                    } else {
                        throw new \RuntimeException(sprintf("Syntax error at %p", $p));
                    }
                } else {
                    $value = self::read($str, $sp_val, $p);

                    if (4 === strlen($value) && 0 === stripos($value, 'NULL')) {
                        $value = null;
                    }
                }

                $result[$key] = $value;
                $state = 3;
            } elseif (3 === $state) {
                if (',' !== $str[$p]) {
                    throw new \RuntimeException(sprintf("Syntax error at %p", $p));
                }

                ++$p;
                $state = 0;
            }

            $p += strspn($str, $spaces, $p);
        };

        if ($state != 0 && $state != 3) {
            throw new \RuntimeException("Unexpected end of string");
        }

        return $result;
    }

    private static function read($str, $spaces, &$p)
    {
        $len   = strcspn($str, $spaces, $p);
        $value = stripcslashes(substr($str, $p, $len));
        $p    += $len;

        return $value;
    }
}
