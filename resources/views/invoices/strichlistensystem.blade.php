@php
    /** @var \App\Models\StrichlistensystemInvoice $invoice */
    $issuer = $invoice->issuer;
    $recipient = $invoice->recipient;
    $price = fn (int $cents) => \App\Helpers\Price::format($cents);
    $clubAddress = trim($issuer['club_street'].', '.$issuer['club_postal_code'].' '.$issuer['club_city'], ', ');
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

        .sender-line {
            position: absolute;
            top: 33mm;
            left: 0;
            width: 85mm;
            font-size: 7pt;
            color: #6b7280;
            border-bottom: 0.3pt solid #9ca3af;
            padding-bottom: 0.8mm;
        }

        .recipient {
            position: absolute;
            top: 39mm;
            left: 0;
            width: 85mm;
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
        <div class="sender-line">{{ $issuer['club_name'] }} · {{ $clubAddress }}</div>
        <div class="recipient">
            {{ $recipient['name'] }}<br>
            {{ $recipient['street'] }}<br>
            {{ $recipient['postal_code'] }} {{ $recipient['city'] }}
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
                <th class="number">Einzelpreis</th>
                <th class="number">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="number">{{ $item['quantity'] }}</td>
                    <td class="number">{{ $price($item['unit_price']) }}</td>
                    <td class="number">{{ $price($item['total']) }}</td>
                </tr>
            @endforeach
            <tr class="sum">
                <td colspan="3" class="number">Summe</td>
                <td class="number">{{ $price($invoice->subtotal) }}</td>
            </tr>
            @if ($invoice->paid > 0)
                <tr class="sum">
                    <td colspan="3" class="number">Bereits bezahlt</td>
                    <td class="number">− {{ $price($invoice->paid) }}</td>
                </tr>
            @endif
            <tr class="total">
                <td colspan="3" class="number">Zu zahlen</td>
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

    @if ($issuer['note'])
        <div class="note">{!! nl2br(e($issuer['note'])) !!}</div>
    @endif
</body>
</html>
