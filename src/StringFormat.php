<?php

namespace Krutush\Template;

class StringFormat{
    public static function ucfirst(string $str): string{
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc.mb_substr($str, 1);
    }
}
