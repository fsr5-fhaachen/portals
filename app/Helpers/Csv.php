<?php

namespace App\Helpers;

/**
 * Helpers for csv exports that are opened with spreadsheet programs like excel.
 */
class Csv
{
    /**
     * Byte order mark, so that excel detects the csv as utf-8.
     *
     * @var string
     */
    public const BOM = "\xEF\xBB\xBF";

    /**
     * Escape a text cell. Spreadsheet programs run cells that start with =, +, -, @, tab or carriage return
     * as formula, a leading ' makes them plain text. Only used for free text like names.
     */
    public static function escapeCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
