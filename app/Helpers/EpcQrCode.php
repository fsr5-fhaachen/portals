<?php

namespace App\Helpers;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use InvalidArgumentException;

/**
 * Payment qr code for a SEPA credit transfer (EPC069-12, also known as "GiroCode"). Most banking apps fill in the
 * transfer (recipient, iban, amount and reference) after scanning it.
 */
class EpcQrCode
{
    /**
     * Maximum length of the name of the recipient.
     *
     * @var int
     */
    public const MAX_NAME_LENGTH = 70;

    /**
     * Maximum length of the unstructured remittance information (reference).
     *
     * @var int
     */
    public const MAX_REFERENCE_LENGTH = 140;

    /**
     * Get the content of the qr code (version 002, UTF-8). The bic is optional within the EEA.
     *
     * @param  int  $amount  amount in cents, between 1 and 99999999999
     */
    public static function payload(string $name, string $iban, ?string $bic, int $amount, string $reference): string
    {
        if ($amount < 1 || $amount > 99999999999) {
            throw new InvalidArgumentException('The amount of a payment qr code must be between 0.01 and 999999999.99 euros.');
        }

        $iban = StrichlistensystemInvoiceSettings::normalizeIban($iban);
        if ($iban === '') {
            throw new InvalidArgumentException('A payment qr code needs an iban.');
        }

        return implode("\n", [
            'BCD',
            '002',
            '1',
            'SCT',
            strtoupper(preg_replace('/\s+/', '', (string) $bic)),
            self::clean($name, self::MAX_NAME_LENGTH),
            $iban,
            'EUR'.number_format($amount / 100, 2, '.', ''),
            '',
            '',
            self::clean($reference, self::MAX_REFERENCE_LENGTH),
        ]);
    }

    /**
     * Get the qr code of the payment as svg (without xml declaration), with the error correction level M as required by
     * the standard.
     *
     * @param  int  $amount  amount in cents
     */
    public static function svg(string $name, string $iban, ?string $bic, int $amount, string $reference, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd));
        $svg = $writer->writeString(self::payload($name, $iban, $bic, $amount, $reference), 'UTF-8', ErrorCorrectionLevel::M());

        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }

    /**
     * Remove line breaks (they separate the fields) and shorten the text to the maximum length.
     */
    private static function clean(string $text, int $maxLength): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', $text)), 0, $maxLength);
    }
}
