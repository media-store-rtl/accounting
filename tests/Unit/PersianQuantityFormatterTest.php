<?php

namespace Tests\Unit;

use App\Support\PersianQuantityFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PersianQuantityFormatterTest extends TestCase
{
    public static function quantities(): array
    {
        return [
            'whole quantity removes trailing zeroes' => [1, '۱'],
            'zero is shown without decimals' => [0, '۰'],
            'fraction uses Persian decimal separator' => [1.5, '۱٫۵'],
            'fraction keeps meaningful precision' => [1.2345, '۱٫۲۳۴۵'],
            'precision is capped at four decimal places' => [1.23456, '۱٫۲۳۴۶'],
            'integer ending in zero is preserved' => [10, '۱۰'],
        ];
    }

    #[DataProvider('quantities')]
    public function test_formats_quantities_for_persian_display(float|int $value, string $expected): void
    {
        self::assertSame($expected, PersianQuantityFormatter::format($value));
    }
}
