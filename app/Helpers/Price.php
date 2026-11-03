<?php

namespace App\Helpers;

/**
 * Formatting of prices of the strichlistensystem. Prices are always stored as integer cents.
 */
class Price
{
    /**
     * Format cents as decimal number with comma (e.g. 150 => "1,50"), so that a german excel detects it as number.
     */
    public static function toDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Format cents as euro (e.g. 150 => "1,50 €").
     */
    public static function format(int $cents): string
    {
        return self::toDecimal($cents).' €';
    }
}
