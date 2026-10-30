<?php

namespace App\Helpers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes, e.g. for urls that should be opened on a tablet or phone without typing them.
 */
class QrCode
{
    /**
     * Get the text as qr code (inline svg without xml declaration).
     */
    public static function svg(string $text, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $writer->writeString($text)));
    }
}
