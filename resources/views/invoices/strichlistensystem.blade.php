@php
    /** @var \App\Models\StrichlistensystemInvoice $invoice */
    $issuer = $invoice->issuer;
    $recipient = $invoice->recipient;
    $price = fn (int $cents) => \App\Helpers\Price::format($cents);
    // the postal code and the city are not separated by a line break, empty parts are left out
    $nonEmpty = fn (array $parts) => array_filter(array_map('trim', $parts), fn (string $part) => $part !== '');
    $clubAddress = implode(', ', $nonEmpty([
        $issuer['club_street'],
        implode("\u{00A0}", $nonEmpty([$issuer['club_postal_code'], $issuer['club_city']])),
    ]));
    $vatBreakdown = $invoice->vatBreakdown();
    // the columns before the amount column of the sum rows
    $sumColspan = $invoice->chargesVat() ? 4 : 3;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->title }}</title>
    <style>
        /* A4 letter (DIN 5008 form B), the footer is repeated on every page */
        @page {
            margin: 12mm 20mm 34mm 25mm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #1f2937;
        }

        .header {
            position: relative;
            height: 74mm;
        }

        .logo {
            position: absolute;
            top: 0;
            right: 0;
            width: 30mm;
        }

        .club-name {
            position: absolute;
            top: 6mm;
            left: 0;
            font-size: 13pt;
            font-weight: bold;
        }

        /* the sender line and the recipient flow in one block, so a wrapped sender line moves the recipient down */
        .address {
            position: absolute;
            top: 33mm;
            left: 0;
            width: 85mm;
        }

        .sender-line {
            font-size: 6.5pt;
            line-height: 1.25;
            color: #6b7280;
            border-bottom: 0.3pt solid #9ca3af;
            padding-bottom: 0.8mm;
        }

        .recipient {
            margin-top: 3mm;
            font-size: 10pt;
            line-height: 1.4;
        }

        .info {
            position: absolute;
            top: 39mm;
            right: 0;
            width: 62mm;
        }

        .info td {
            padding: 0.4mm 0;
            vertical-align: top;
        }

        .info td.label {
            color: #6b7280;
            padding-right: 3mm;
        }

        h1 {
            font-size: 12pt;
            margin: 0 0 4mm;
        }

        p {
            margin: 0 0 3mm;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0 4mm;
        }

        table.items th {
            text-align: left;
            font-weight: bold;
            border-bottom: 0.8pt solid #1f2937;
            padding: 1.2mm 1.5mm;
        }

        table.items td {
            border-bottom: 0.3pt solid #d1d5db;
            padding: 0.9mm 1.5mm;
        }

        table.items .number {
            text-align: right;
            white-space: nowrap;
        }

        table.items tr.sum td {
            border-bottom: none;
            padding-top: 1mm;
            padding-bottom: 1mm;
        }

        /* the included vat is a breakdown of the sum, not an addition */
        table.items tr.vat td {
            border-bottom: none;
            padding-top: 0;
            padding-bottom: 0.6mm;
            font-size: 8pt;
            color: #6b7280;
        }

        table.items tr.total td {
            font-weight: bold;
            font-size: 10pt;
            border-top: 0.8pt solid #1f2937;
            border-bottom: none;
        }

        /* the wrapper keeps the payment together, dompdf ignores the rule on tables */
        .payment-wrapper {
            margin-top: 4mm;
            page-break-inside: avoid;
        }

        .payment {
            width: 100%;
            border: 0.5pt solid #d1d5db;
            border-collapse: collapse;
        }

        .payment td {
            padding: 3mm;
            vertical-align: top;
        }

        .payment .account td {
            padding: 0.4mm 3mm 0.4mm 0;
        }

        .payment .qr {
            width: 38mm;
            text-align: center;
            font-size: 7pt;
            color: #6b7280;
        }

        .payment .qr img {
            width: 34mm;
            height: 34mm;
        }

        .note {
            margin-top: 4mm;
            font-size: 8pt;
            color: #4b5563;
        }

        .footer {
            position: fixed;
            bottom: -26mm;
            left: 0;
            right: 0;
            height: 20mm;
            border-top: 0.3pt solid #9ca3af;
            padding-top: 2mm;
            font-size: 7pt;
            color: #6b7280;
        }

        .footer table {
            width: 100%;
        }

        .footer td {
            vertical-align: top;
            width: 33%;
            padding-right: 3mm;
        }
    </style>
</head>
<body>
    <div class="footer">
        <table>
            <tr>
                <td>
                    <strong>{{ $issuer['club_name'] }}</strong><br>
                    {{ $issuer['club_street'] }}<br>
                    {{ $issuer['club_postal_code'] }} {{ $issuer['club_city'] }}
                    @if ($issuer['email'])
                        <br>{{ $issuer['email'] }}
                    @endif
                    @if (! empty($issuer['tax_number']))
                        <br>Steuernummer: {{ $issuer['tax_number'] }}
                    @endif
                    @if (! empty($issuer['vat_id']))
                        <br>USt-IdNr.: {{ $issuer['vat_id'] }}
                    @endif
                </td>
                <td>
                    <strong>Vorstand</strong><br>
                    {{ $issuer['chairperson'] }}<br>
                    <strong>Stellv. Vorstand</strong><br>
                    {{ $issuer['deputy_chairperson'] }}
                </td>
                <td>
                    <strong>Bankverbindung</strong><br>
                    @if ($issuer['bank_name'])
                        {{ $issuer['bank_name'] }}<br>
                    @endif
                    IBAN: {{ \App\Helpers\StrichlistensystemInvoiceSettings::formatIban($issuer['iban']) }}
                    @if ($issuer['bic'])
                        <br>BIC: {{ $issuer['bic'] }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="header">
        <img class="logo" src="{{ $logo }}" alt="Logo">
        <div class="club-name">{{ $issuer['club_name'] }}</div>
        <div class="address">
            <div class="sender-line">{{ $issuer['club_name'] }} · {{ $clubAddress }}</div>
            <div class="recipient">
                {{ $recipient['name'] }}<br>
                {{ $recipient['street'] }}<br>
                {{ $recipient['postal_code'] }} {{ $recipient['city'] }}
            </div>
        </div>
        <table class="info">
            <tr>
                <td class="label">Rechnungsnummer</td>
                <td><strong>{{ $invoice->number }}</strong></td>
            </tr>
            <tr>
                <td class="label">Rechnungsdatum</td>
                <td>{{ $invoice->issued_on->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td class="label">Zahlbar bis</td>
                <td>{{ $invoice->due_on->format('d.m.Y') }}</td>
            </tr>
            @if (! empty($issuer['tax_number']))
                <tr>
                    <td class="label">Steuernummer</td>
                    <td>{{ $issuer['tax_number'] }}</td>
                </tr>
            @endif
            @if (! empty($issuer['vat_id']))
                <tr>
                    <td class="label">USt-IdNr.</td>
                    <td>{{ $issuer['vat_id'] }}</td>
                </tr>
            @endif
        </table>
    </div>

    <h1>{{ $invoice->title }}</h1>

    <p>Hallo {{ $recipient['name'] }},</p>
    <p>vielen Dank für deinen Besuch. Hiermit stellen wir dir die folgenden Posten in Rechnung:</p>

    <table class="items">
        <thead>
            <tr>
                <th>Artikel</th>
                <th class="number">Menge</th>
                <th class="number">Einzelpreis{{ $invoice->chargesVat() ? ' (brutto)' : '' }}</th>
                @if ($invoice->chargesVat())
                    <th class="number">MwSt.</th>
                @endif
                <th class="number">Gesamt{{ $invoice->chargesVat() ? ' (brutto)' : '' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="number">{{ $item['quantity'] }}</td>
                    <td class="number">{{ $price($item['unit_price']) }}</td>
                    @if ($invoice->chargesVat())
                        <td class="number">{{ (int) ($item['vat_rate'] ?? 0) }} %</td>
                    @endif
                    <td class="number">{{ $price($item['total']) }}</td>
                </tr>
            @endforeach
            <tr class="sum">
                <td colspan="{{ $sumColspan }}" class="number">{{ $invoice->chargesVat() ? 'Summe (brutto)' : 'Summe' }}</td>
                <td class="number">{{ $price($invoice->subtotal) }}</td>
            </tr>
            {{-- the prices are gross prices, so the vat is included and not added --}}
            @foreach ($vatBreakdown as $vat)
                <tr class="vat">
                    <td colspan="{{ $sumColspan }}" class="number">darin enthaltene {{ $vat['rate'] }} % MwSt. (netto {{ $price($vat['net']) }})</td>
                    <td class="number">{{ $price($vat['vat']) }}</td>
                </tr>
            @endforeach
            @if ($invoice->paid > 0)
                <tr class="sum">
                    <td colspan="{{ $sumColspan }}" class="number">Bereits bezahlt</td>
                    <td class="number">− {{ $price($invoice->paid) }}</td>
                </tr>
            @endif
            <tr class="total">
                <td colspan="{{ $sumColspan }}" class="number">Zu zahlen</td>
                <td class="number">{{ $price($invoice->amount_due) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($invoice->amount_due > 0)
        <div class="payment-wrapper">
        <table class="payment">
            <tr>
                <td>
                    <p>
                        Bitte überweise <strong>{{ $price($invoice->amount_due) }}</strong> bis zum
                        <strong>{{ $invoice->due_on->format('d.m.Y') }}</strong> auf das folgende Konto:
                    </p>
                    <table class="account">
                        <tr>
                            <td>Empfänger</td>
                            <td><strong>{{ $issuer['account_holder'] }}</strong></td>
                        </tr>
                        <tr>
                            <td>IBAN</td>
                            <td><strong>{{ \App\Helpers\StrichlistensystemInvoiceSettings::formatIban($issuer['iban']) }}</strong></td>
                        </tr>
                        @if ($issuer['bic'])
                            <tr>
                                <td>BIC</td>
                                <td>{{ $issuer['bic'] }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Verwendungszweck</td>
                            <td><strong>{{ $invoice->number }} {{ $recipient['name'] }}</strong></td>
                        </tr>
                    </table>
                </td>
                @if ($qrCode)
                    <td class="qr">
                        <img src="{{ $qrCode }}" alt="Zahlungs-QR-Code"><br>
                        Mit der Banking-App scannen
                    </td>
                @endif
            </tr>
        </table>
        </div>
    @else
        <p>Der Betrag ist bereits vollständig bezahlt.</p>
    @endif

    @if ($invoice->chargesVat())
        <div class="note">Alle Preise sind Bruttopreise und enthalten die angegebene Mehrwertsteuer.</div>
    @endif

    @if ($issuer['note'])
        <div class="note">{!! nl2br(e($issuer['note'])) !!}</div>
    @endif
</body>
</html>
