<?php

namespace App\Helpers;

class StringHelper
{
    /**
     * Returns string without spaces at the beginning and at the end, and all white-spaces inside are replaced with
     * single space character.
     *
     * @param string $str
     * @return string
     */
    public static function trimRedundantWhiteSpaces(string $str): string
    {
        return preg_replace('/(?:\s|\xc2\xa0)+/', "\x20", trim($str));
    }
}
