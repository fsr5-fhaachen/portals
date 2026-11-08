<?php

namespace App\Helpers;

use App\Models\State;

/**
 * Settings of the invoices of the strichlistensystem (issuer, bank account, number range and title), entered by the
 * admins and stored as state.
 */
class StrichlistensystemInvoiceSettings
{
    /**
     * Key of the state with the settings.
     *
     * @var string
     */
    public const KEY = 'strichlistensystemInvoiceSettings';

    /**
     * Placeholders of the title template and their description.
     *
     * @var array<string, string>
     */
    public const TITLE_PLACEHOLDERS = [
        '{vorname}' => 'Vorname',
        '{nachname}' => 'Nachname',
        '{name}' => 'Vollständiger Name der Rechnungsadresse',
        '{nummer}' => 'Rechnungsnummer',
        '{datum}' => 'Rechnungsdatum',
    ];

    /**
     * Required settings with their labels, an invoice can only be created if all of them are set.
     *
     * @var array<string, string>
     */
    private const REQUIRED = [
        'club_name' => 'Name des Vereins',
        'club_street' => 'Straße des Vereins',
        'club_postal_code' => 'PLZ des Vereins',
        'club_city' => 'Ort des Vereins',
        'chairperson' => 'Vorstand',
        'deputy_chairperson' => 'Stellvertretender Vorstand',
        'iban' => 'IBAN',
        'number_prefix' => 'Präfix der Rechnungsnummer',
        'title_template' => 'Titel der Rechnung',
    ];

    /**
     * Get the settings with defaults for the missing ones.
     *
     * @return array{club_name: string, club_street: string, club_postal_code: string, club_city: string, chairperson: string, deputy_chairperson: string, email: string, charges_vat: bool, tax_number: string, vat_id: string, account_holder: string, iban: string, bic: string, bank_name: string, number_prefix: string, number_digits: int, start_number: int, title_template: string, payment_term_days: int, note: string}
     */
    public function settings(): array
    {
        $stored = json_decode(State::where('key', self::KEY)->value('value') ?? '{}', true) ?: [];

        return [
            'club_name' => (string) ($stored['club_name'] ?? ''),
            'club_street' => (string) ($stored['club_street'] ?? ''),
            'club_postal_code' => (string) ($stored['club_postal_code'] ?? ''),
            'club_city' => (string) ($stored['club_city'] ?? ''),
            'chairperson' => (string) ($stored['chairperson'] ?? ''),
            'deputy_chairperson' => (string) ($stored['deputy_chairperson'] ?? ''),
            'email' => (string) ($stored['email'] ?? ''),
            'charges_vat' => (bool) ($stored['charges_vat'] ?? true),
            'tax_number' => (string) ($stored['tax_number'] ?? ''),
            'vat_id' => (string) ($stored['vat_id'] ?? ''),
            'account_holder' => (string) ($stored['account_holder'] ?? ''),
            'iban' => (string) ($stored['iban'] ?? ''),
            'bic' => (string) ($stored['bic'] ?? ''),
            'bank_name' => (string) ($stored['bank_name'] ?? ''),
            'number_prefix' => (string) ($stored['number_prefix'] ?? 'RE-'),
            'number_digits' => (int) ($stored['number_digits'] ?? 4),
            'start_number' => (int) ($stored['start_number'] ?? 1),
            'title_template' => (string) ($stored['title_template'] ?? 'Abrechnung Strichliste - {name} - {nummer}'),
            'payment_term_days' => (int) ($stored['payment_term_days'] ?? 14),
            'note' => (string) ($stored['note'] ?? ''),
        ];
    }

    /**
     * Store the settings. The iban, the bic and the vat id are stored without spaces and in upper case.
     *
     * @param  array<string, mixed>  $settings
     */
    public function save(array $settings): void
    {
        $settings['iban'] = self::normalizeIban((string) ($settings['iban'] ?? ''));
        $settings['bic'] = strtoupper(preg_replace('/\s+/', '', (string) ($settings['bic'] ?? '')));
        $settings['vat_id'] = strtoupper(preg_replace('/\s+/', '', (string) ($settings['vat_id'] ?? '')));
        if (array_key_exists('charges_vat', $settings)) {
            $settings['charges_vat'] = filter_var($settings['charges_vat'], FILTER_VALIDATE_BOOLEAN);
        }

        State::updateOrCreate(['key' => self::KEY], ['value' => json_encode(array_merge($this->settings(), $settings))]);
    }

    /**
     * Get the labels of the required settings that are missing. Invoices with vat need the tax number or the vat id
     * of the club (§ 14 Abs. 4 Nr. 2 UStG).
     *
     * @return list<string>
     */
    public function missing(): array
    {
        $settings = $this->settings();

        $missing = array_values(array_filter(
            self::REQUIRED,
            fn (string $label, string $key) => trim((string) $settings[$key]) === '',
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($settings['charges_vat'] && trim($settings['tax_number']) === '' && trim($settings['vat_id']) === '') {
            $missing[] = 'Steuernummer oder Umsatzsteuer-ID';
        }

        return $missing;
    }

    /**
     * Format the invoice number of the sequence, e.g. "GER26-0007".
     */
    public function formatNumber(int $sequence): string
    {
        $settings = $this->settings();

        return $settings['number_prefix'].str_pad((string) $sequence, max(1, $settings['number_digits']), '0', STR_PAD_LEFT);
    }

    /**
     * Get the account holder, the name of the club if no other account holder is set.
     */
    public function accountHolder(): string
    {
        $settings = $this->settings();

        return trim($settings['account_holder']) !== '' ? $settings['account_holder'] : $settings['club_name'];
    }

    /**
     * Remove the spaces of an iban and convert it to upper case.
     */
    public static function normalizeIban(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', $iban));
    }

    /**
     * Check the checksum of an iban (ISO 13616, modulo 97).
     */
    public static function isValidIban(string $iban): bool
    {
        $iban = self::normalizeIban($iban);
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban)) {
            return false;
        }

        $digits = '';
        foreach (str_split(substr($iban, 4).substr($iban, 0, 4)) as $character) {
            $digits .= ctype_alpha($character) ? (string) (ord($character) - 55) : $character;
        }

        $remainder = 0;
        foreach (str_split($digits, 7) as $chunk) {
            $remainder = (int) ($remainder.$chunk) % 97;
        }

        return $remainder === 1;
    }

    /**
     * Format an iban in groups of four characters, e.g. "DE89 3704 0044 0532 0130 00".
     */
    public static function formatIban(string $iban): string
    {
        return trim(chunk_split(self::normalizeIban($iban), 4, ' '));
    }
}
