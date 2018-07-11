<?php

namespace Krutush\Database;

class TypeHelper{
    private static function dateTimeConverter(string $format, $value): string{
        return (is_a($value, \DateTime::class) ? $value : new \DateTime(strval($value)))->format($format);
    }

    public static function dateConvert($value): string{
        return static::dateTimeConverter('Y-m-d', $value);
    }

    public static function timeConvert($value): string{
        return static::dateTimeConverter('H:i:s', $value);
    }

    public static function datetimeConvert($value): string{
        return static::dateTimeConverter('Y-m-d H:i:s', $value);
    }
}