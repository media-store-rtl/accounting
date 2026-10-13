<?php

namespace App\Support;

final class PersianQuantityFormatter
{
    public static function format(mixed $value): string
    {
        $number = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

        return strtr($number, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
            '.' => '٫',
        ]);
    }
}
