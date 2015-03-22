<?php

namespace Intaro\HStore;

final class Coder
{
    static function encode(array $arr)
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

    static function decode($str)
    {
        static $spaces = " \t\r\n";

        $len = strlen($str);

        // skip spaces from right
        while ($len > 0 && false !== strpos($spaces, $str[$len - 1])) {
            --$len;
        }

        if (0 === $len) {
            return [];
        }

        $p = 0;

        $result = array();
        $quoted = null;

        while ($p < $len) {
            $p += strspn($str, $spaces, $p);
            $c = $str[$p];

            // Next element.
            if (',' == $c) {
                ++$p;
                continue;
            }

            // Key.
            $key = self::readString($str, $p, $quoted);

            // '=>' sequence.
            $p += strspn($str, $spaces, $p);
            if ($p !== strpos($str, '=>', $p)) {
                throw new \RuntimeException($str);
            }

            $p += 2;
            $p += strspn($str, $spaces, $p);

            // Value.
            $value = self::readString($str, $p, $quoted);
            if (!$quoted && 4 === strlen($value) && 0 === stripos($value, 'NULL')) {
                $result[$key] = null;
            } else {
                $result[$key] = $value;
            }
        }

        if ($p != $len) {
            throw new \RuntimeException($str);
        }

        return $result;
    }

    private static function readString($str, &$p, &$quoted)
    {
        $c = isset($str[$p]) ? $str[$p] : false;

        // Unquoted string.
        if ($c != '"') {
            $quoted = false;
            $len = strcspn($str, " \r\n\t,=>", $p);
            $value = substr($str, $p, $len);
            $p += $len;

            return stripcslashes($value);
        }

        // Quoted string.
        $quoted = true;
        $m = null;
        if (preg_match('/" ((?' . '>[^"\\\\]+|\\\\.)*) "/Asx', $str, $m, 0, $p)) {
            $value = stripcslashes($m[1]);
            $p += strlen($m[0]);

            return $value;
        }

        throw new \RuntimeException($str);
    }
}
