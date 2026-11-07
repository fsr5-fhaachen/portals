<?php

namespace App\Helpers;

use App\Models\StrichlistensystemInvoice;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Pdf of an invoice of the strichlistensystem. The pdf is rendered from the stored invoice, so it is always the same.
 */
class StrichlistensystemInvoicePdf
{
    /**
     * Logo on the invoice, a small jpeg without transparency (the pdf renderer needs no image extension for it).
     *
     * @var string
     */
    public const LOGO_PATH = 'images/invoice-logo.jpg';

    /**
     * Render the pdf of the invoice.
     */
    public static function render(StrichlistensystemInvoice $invoice): string
    {
        $qrCode = StrichlistensystemInvoicing::paymentQrCode($invoice);

        return Pdf::loadView('invoices.strichlistensystem', [
            'invoice' => $invoice,
            'logo' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents(resource_path(self::LOGO_PATH))),
            'qrCode' => $qrCode === null ? null : 'data:image/svg+xml;base64,'.base64_encode($qrCode),
        ])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'isFontSubsettingEnabled' => true, 'defaultFont' => 'DejaVu Sans'])
            ->output();
    }
}
