<?php

namespace Tests\Feature;

use App\Helpers\EpcQrCode;
use App\Helpers\StrichlistensystemInvoicePdf;
use App\Helpers\StrichlistensystemInvoiceSettings;
use App\Helpers\StrichlistensystemInvoicing;
use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\StrichlistensystemInvoice;
use App\Models\StrichlistensystemPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use Tests\TestCase;

class StrichlistensystemInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Article $beer;

    private Article $cola;

    /**
     * Complete settings of the invoices.
     *
     * @var array<string, mixed>
     */
    private const SETTINGS = [
        'club_name' => 'Fachschaftsrat 5 e.V.',
        'club_street' => 'Eupener Straße 70',
        'club_postal_code' => '52066',
        'club_city' => 'Aachen',
        'chairperson' => 'Max Mustermann',
        'deputy_chairperson' => 'Erika Musterfrau',
        'email' => 'kasse@example.org',
        'charges_vat' => true,
        'tax_number' => '201/123/45678',
        'vat_id' => 'de 123 456 789',
        'account_holder' => '',
        'iban' => 'DE89 3704 0044 0532 0130 00',
        'bic' => 'COBADEFFXXX',
        'bank_name' => 'Commerzbank',
        'number_prefix' => 'GER26-',
        'number_digits' => 4,
        'start_number' => 1,
        'title_template' => 'Gerolstein 2026 - Abrechnung Getränke - {vorname} {nachname} - {nummer}',
        'payment_term_days' => 14,
        'note' => 'Bei Fragen melde dich bei der Kasse.',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->activateModule('strichlistensystem');

        $this->admin = $this->createUserWithRoles('admin', 'tutor');
        $this->beer = Article::factory()->create(['name' => 'Bier', 'price' => 100, 'show_order' => 1]);
        $this->cola = Article::factory()->create(['name' => 'Cola', 'price' => 50, 'show_order' => 2]);
    }

    /**
     * Save complete invoice settings, with the given changes.
     *
     * @param  array<string, mixed>  $changes
     */
    private function saveSettings(array $changes = []): void
    {
        app(StrichlistensystemInvoiceSettings::class)->save(array_merge(self::SETTINGS, $changes));
    }

    /**
     * Create a user that wants to pay by bank transfer with a complete address.
     */
    private function payer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'firstname' => 'Anna',
            'lastname' => 'Müller',
            'wants_bank_transfer' => true,
            'billing_name' => 'Anna Müller',
            'billing_street' => 'Hauptstraße 1',
            'billing_postal_code' => '52062',
            'billing_city' => 'Aachen',
        ], $attributes));
    }

    /**
     * Book the article for the user.
     */
    private function book(User $user, Article $article, int $amount = 1): void
    {
        ArticleActionLog::factory()->count($amount)->for($user)->for($article)->create();
    }

    /**
     * Create the invoice of the user as the admin.
     */
    private function createInvoice(User $user): StrichlistensystemInvoice
    {
        return app(StrichlistensystemInvoicing::class)->create($user, $this->admin);
    }

    public function test_user_can_save_the_billing_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.updateBillingDetails'), [
                'wants_bank_transfer' => true,
                'name' => ' Anna Müller ',
                'street' => 'Hauptstraße 1',
                'postal_code' => '52062',
                'city' => 'Aachen',
            ])
            ->assertRedirect(route('strichlistensystem.index'));

        $user->refresh();
        $this->assertTrue($user->wants_bank_transfer);
        $this->assertSame(['name' => 'Anna Müller', 'street' => 'Hauptstraße 1', 'postal_code' => '52062', 'city' => 'Aachen'], $user->billingDetails());
    }

    public function test_billing_details_are_required_for_a_bank_transfer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.updateBillingDetails'), ['wants_bank_transfer' => true, 'name' => 'Anna Müller'])
            ->assertSessionHasErrors(['street', 'postal_code', 'city']);

        $this->assertFalse((bool) $user->refresh()->wants_bank_transfer);

        // without a bank transfer no address is needed, a saved address is kept
        $payer = $this->payer();
        $this->actingAs($payer)
            ->post(route('strichlistensystem.updateBillingDetails'), ['wants_bank_transfer' => false])
            ->assertSessionHasNoErrors();

        $payer->refresh();
        $this->assertFalse($payer->wants_bank_transfer);
        $this->assertSame('Hauptstraße 1', $payer->billing_street);
    }

    public function test_self_service_suggests_the_name_with_upper_case_initials(): void
    {
        $user = User::factory()->create(['firstname' => 'anna-lena', 'lastname' => 'von der heide', 'pin' => '4826']);

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('billingDetails.name', 'Anna-Lena Von Der Heide')
                ->where('billingDetails.wants_bank_transfer', false)
            );

        // a fresh instance, the request added attributes to the logged in user
        $user = User::findOrFail($user->id);
        $user->update(['billing_name' => 'Anna-Lena von der Heide']);

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page->where('billingDetails.name', 'Anna-Lena von der Heide'));
    }

    public function test_admin_can_save_the_settings(): void
    {
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.strichlistensystem.updateInvoiceSettings'), self::SETTINGS)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.admin.strichlistensystem.invoiceSettings'));

        $settings = app(StrichlistensystemInvoiceSettings::class);
        $this->assertSame('DE89370400440532013000', $settings->settings()['iban']);
        $this->assertSame('DE123456789', $settings->settings()['vat_id']);
        $this->assertSame('201/123/45678', $settings->settings()['tax_number']);
        $this->assertTrue($settings->settings()['charges_vat']);
        $this->assertSame([], $settings->missing());
        $this->assertSame('Fachschaftsrat 5 e.V.', $settings->accountHolder());

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.strichlistensystem.invoiceSettings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Strichlistensystem/InvoiceSettings')
                ->where('nextNumber', 'GER26-0001')
                ->where('missingSettings', [])
            );
    }

    public function test_settings_reject_an_invalid_iban_and_bic(): void
    {
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.strichlistensystem.updateInvoiceSettings'), array_merge(self::SETTINGS, [
                'iban' => 'DE89 3704 0044 0532 0130 01',
                'bic' => 'NOBIC',
                'number_prefix' => 'GER 26',
                'vat_id' => '123456789',
                'tax_number' => 'St-Nr. 201',
                'charges_vat' => 'maybe',
            ]))
            ->assertSessionHasErrors(['iban', 'bic', 'number_prefix', 'vat_id', 'tax_number', 'charges_vat']);

        $this->assertNotSame([], app(StrichlistensystemInvoiceSettings::class)->missing());
    }

    public function test_settings_are_only_for_admins_with_the_permission(): void
    {
        $this->actingAsTutor($this->createUserWithRoles('tutor'))
            ->get(route('dashboard.admin.strichlistensystem.invoiceSettings'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_invoice_contains_the_grouped_bookings_and_the_snapshot(): void
    {
        $this->saveSettings();
        $user = $this->payer();
        $this->book($user, $this->cola, 2);
        $this->book($user, $this->beer, 3);
        // bookings with another price, cancelled bookings and bookings without a price are handled separately
        $this->beer->update(['price' => 120]);
        $this->book($user, $this->beer);
        ArticleActionLog::factory()->for($user)->for($this->cola)->create()->delete();
        ArticleActionLog::factory()->for($user)->for(Article::factory()->withoutPrice())->create();

        $invoice = $this->createInvoice($user);

        $this->assertSame(1, $invoice->sequence);
        $this->assertSame('GER26-0001', $invoice->number);
        $this->assertSame('Gerolstein 2026 - Abrechnung Getränke - Anna Müller - GER26-0001', $invoice->title);
        $this->assertSame([
            ['name' => 'Bier', 'quantity' => 3, 'unit_price' => 100, 'total' => 300, 'vat_rate' => 19],
            ['name' => 'Bier', 'quantity' => 1, 'unit_price' => 120, 'total' => 120, 'vat_rate' => 19],
            ['name' => 'Cola', 'quantity' => 2, 'unit_price' => 50, 'total' => 100, 'vat_rate' => 19],
        ], $invoice->items);
        $this->assertSame(520, $invoice->subtotal);
        $this->assertSame(0, $invoice->paid);
        $this->assertSame(520, $invoice->amount_due);
        $this->assertSame('Anna Müller', $invoice->recipient['name']);
        $this->assertSame('Fachschaftsrat 5 e.V.', $invoice->issuer['account_holder']);
        $this->assertSame('DE123456789', $invoice->issuer['vat_id']);
        $this->assertSame('201/123/45678', $invoice->issuer['tax_number']);
        // the prices are gross prices with 19 % vat included
        $this->assertTrue($invoice->chargesVat());
        $this->assertSame([['rate' => 19, 'gross' => 520, 'net' => 437, 'vat' => 83]], $invoice->vatBreakdown());
        $this->assertSame(83, $invoice->vatAmount());
        $this->assertSame(437, $invoice->netAmount());
        $this->assertSame(now()->addDays(14)->toDateString(), $invoice->due_on->toDateString());
        $this->assertSame($this->admin->id, $invoice->created_by_id);
        $this->assertSame(6, $invoice->articleActionLogs()->count());

        // later changes do not change the issued invoice
        $this->saveSettings(['club_name' => 'Anderer Verein']);
        $user->update(['billing_street' => 'Neue Straße 2']);
        $invoice->refresh();
        $this->assertSame('Fachschaftsrat 5 e.V.', $invoice->issuer['club_name']);
        $this->assertSame('Hauptstraße 1', $invoice->recipient['street']);
    }

    public function test_numbers_start_at_the_start_number_and_are_never_reused(): void
    {
        $this->saveSettings(['start_number' => 42, 'number_digits' => 5]);
        $first = $this->payer();
        $second = $this->payer();
        $this->book($first, $this->beer);
        $this->book($second, $this->beer);

        $this->assertSame('GER26-00042', $this->createInvoice($first)->number);
        app(StrichlistensystemInvoicing::class)->cancel(StrichlistensystemInvoice::first());
        $this->assertSame('GER26-00043', $this->createInvoice($second)->number);
        // the bookings of the cancelled invoice get a new number
        $this->assertSame('GER26-00044', $this->createInvoice($first)->number);

        // a higher start number continues from there, a lower one is ignored
        $this->saveSettings(['start_number' => 100, 'number_digits' => 5]);
        $this->book($first, $this->beer);
        $this->assertSame('GER26-00100', $this->createInvoice($first)->number);
        $this->saveSettings(['start_number' => 1, 'number_digits' => 5]);
        $this->book($first, $this->beer);
        $this->assertSame('GER26-00101', $this->createInvoice($first)->number);
    }

    public function test_payments_are_deducted_and_cover_earlier_invoices_first(): void
    {
        $this->saveSettings();
        $user = $this->payer();
        $this->book($user, $this->beer, 10);
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 400]);

        $first = $this->createInvoice($user);
        $this->assertSame([1000, 400, 600], [$first->subtotal, $first->paid, $first->amount_due]);

        // the payment of the first invoice is not deducted twice, an overpayment is
        $this->book($user, $this->cola, 4);
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 700]);
        $second = $this->createInvoice($user);
        $this->assertSame([200, 100, 100], [$second->subtotal, $second->paid, $second->amount_due]);
    }

    public function test_no_invoice_without_complete_data_or_open_bookings(): void
    {
        $invoicing = app(StrichlistensystemInvoicing::class);
        $user = $this->payer(['wants_bank_transfer' => false]);

        $this->assertCount(3, $invoicing->problems($user));

        $this->saveSettings();
        $user->update(['wants_bank_transfer' => true, 'billing_city' => '']);
        $this->book($user, $this->beer);
        $this->assertSame(['Die Rechnungsadresse der Person ist unvollständig.'], $invoicing->problems($user));

        $user->update(['billing_city' => 'Aachen']);
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 100]);
        $this->assertSame(['Die noch nicht abgerechneten Buchungen sind schon bezahlt.'], $invoicing->problems($user->refresh()));

        $this->actingAsTutor($this->admin)
            ->from(route('dashboard.admin.strichlistensystem.billing'))
            ->post(route('dashboard.admin.strichlistensystem.storeInvoice', $user))
            ->assertRedirect(route('dashboard.admin.strichlistensystem.billing'))
            ->assertSessionHas('error');
        $this->assertSame(0, StrichlistensystemInvoice::count());
    }

    public function test_admin_can_create_an_invoice_in_the_payment_dialog(): void
    {
        $this->saveSettings();
        $user = $this->payer();
        $this->book($user, $this->beer, 2);

        $this->actingAsTutor($this->admin)
            ->getJson(route('dashboard.admin.strichlistensystem.billingUser', $user))
            ->assertOk()
            ->assertJsonPath('billing.wants_bank_transfer', true)
            ->assertJsonPath('billing.details.city', 'Aachen')
            ->assertJsonPath('billing.problems', [])
            ->assertJsonPath('billing.preview.amount_due', 200)
            ->assertJsonPath('billing.invoices', []);

        $this->actingAsTutor($this->admin)
            ->from(route('dashboard.admin.strichlistensystem.billing'))
            ->post(route('dashboard.admin.strichlistensystem.storeInvoice', $user))
            ->assertRedirect(route('dashboard.admin.strichlistensystem.billing'))
            ->assertSessionHas('success');

        $this->actingAsTutor($this->admin)
            ->getJson(route('dashboard.admin.strichlistensystem.billingUser', $user))
            ->assertJsonPath('billing.invoices.0.number', 'GER26-0001')
            ->assertJsonPath('billing.problems', ['Es gibt keine Buchungen, die noch nicht abgerechnet sind.']);
    }

    public function test_invoiced_bookings_can_not_be_cancelled_by_the_user(): void
    {
        $this->saveSettings();
        $user = $this->payer();
        $this->book($user, $this->beer);
        $booking = ArticleActionLog::first();

        $this->assertTrue($booking->isCancellable());
        $this->createInvoice($user);
        $this->assertFalse($booking->refresh()->isCancellable());
    }

    public function test_cancelled_invoice_releases_its_bookings(): void
    {
        $this->saveSettings();
        $user = $this->payer();
        $this->book($user, $this->beer, 2);
        $invoice = $this->createInvoice($user);

        $this->actingAsTutor($this->admin)
            ->from(route('dashboard.admin.strichlistensystem.invoices'))
            ->post(route('dashboard.admin.strichlistensystem.cancelInvoice', $invoice))
            ->assertSessionHas('success');

        $this->assertNotNull($invoice->refresh()->cancelled_at);
        $this->assertSame(0, ArticleActionLog::whereNotNull('invoice_id')->count());

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.strichlistensystem.cancelInvoice', $invoice))
            ->assertSessionHas('error');
    }

    public function test_invoice_list_shows_the_status(): void
    {
        $this->saveSettings();
        $paidUser = $this->payer(['lastname' => 'Bezahlt']);
        $openUser = $this->payer(['lastname' => 'Offen']);
        $this->book($paidUser, $this->beer);
        $this->book($openUser, $this->beer);
        $paid = $this->createInvoice($paidUser);
        $open = $this->createInvoice($openUser);
        StrichlistensystemPayment::factory()->for($paidUser)->create(['amount' => 100]);
        $this->book($openUser, $this->cola);
        $cancelled = $this->createInvoice($openUser);
        app(StrichlistensystemInvoicing::class)->cancel($cancelled);

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.strichlistensystem.invoices'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Strichlistensystem/Invoices')
                ->has('invoices.data', 3)
                ->where('invoices.data.0.id', $cancelled->id)
                ->where('invoices.data.0.status', 'cancelled')
                ->where('invoices.data.1.id', $open->id)
                ->where('invoices.data.1.status', 'open')
                ->where('invoices.data.2.id', $paid->id)
                ->where('invoices.data.2.status', 'paid')
                ->where('invoices.data.2.user.lastname', 'Bezahlt')
            );

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.strichlistensystem.invoices', ['search' => 'offen']))
            ->assertInertia(fn (Assert $page) => $page->has('invoices.data', 2));
    }

    public function test_admin_can_download_the_pdf(): void
    {
        $this->saveSettings();
        $user = $this->payer();
        $this->book($user, $this->beer, 2);
        $invoice = $this->createInvoice($user);

        $response = $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.strichlistensystem.invoicePdf', $invoice))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="GER26-0001.pdf"');

        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $this->actingAsTutor($this->createUserWithRoles('tutor'))
            ->get(route('dashboard.admin.strichlistensystem.invoicePdf', $invoice))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_settings_without_vat(): void
    {
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.strichlistensystem.updateInvoiceSettings'), array_merge(self::SETTINGS, ['charges_vat' => false, 'tax_number' => '', 'vat_id' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame([], app(StrichlistensystemInvoiceSettings::class)->missing());

        $user = $this->payer();
        $this->book($user, $this->beer, 2);
        $invoice = $this->createInvoice($user);

        $this->assertSame('', $invoice->issuer['vat_id']);
        $this->assertFalse($invoice->chargesVat());
        $this->assertSame(0, $invoice->items[0]['vat_rate']);
        $this->assertSame([], $invoice->vatBreakdown());
        $this->assertSame(0, $invoice->vatAmount());
        $this->assertSame(200, $invoice->netAmount());
        $this->assertStringStartsWith('%PDF-', StrichlistensystemInvoicePdf::render($invoice));
    }

    public function test_vat_needs_the_tax_number_or_the_vat_id(): void
    {
        $this->saveSettings(['tax_number' => '', 'vat_id' => '']);
        $settings = app(StrichlistensystemInvoiceSettings::class);
        $this->assertSame(['Steuernummer oder Umsatzsteuer-ID'], $settings->missing());

        $user = $this->payer();
        $this->book($user, $this->beer);
        $this->assertNotSame([], app(StrichlistensystemInvoicing::class)->problems($user));

        $this->saveSettings(['tax_number' => '', 'vat_id' => 'DE123456789']);
        $this->assertSame([], $settings->missing());

        $this->saveSettings(['tax_number' => '201/123/45678', 'vat_id' => '']);
        $this->assertSame([], $settings->missing());
    }

    public function test_invoice_shows_the_vat_per_rate_of_the_articles(): void
    {
        $this->saveSettings();
        $chips = Article::factory()->create(['name' => 'Chips', 'price' => 50, 'vat_rate' => 7, 'show_order' => 3]);
        $user = $this->payer();
        $this->book($user, $this->beer, 2);
        $this->book($user, $chips, 3);

        $invoice = $this->createInvoice($user);

        $this->assertSame(19, $invoice->items[0]['vat_rate']);
        $this->assertSame(7, $invoice->items[1]['vat_rate']);
        $this->assertSame([
            ['rate' => 19, 'gross' => 200, 'net' => 168, 'vat' => 32],
            ['rate' => 7, 'gross' => 150, 'net' => 140, 'vat' => 10],
        ], $invoice->vatBreakdown());
        $this->assertSame(42, $invoice->vatAmount());
        $this->assertSame(308, $invoice->netAmount());

        // a later change of the rate does not change the issued invoice
        $chips->update(['vat_rate' => 19]);
        $this->assertSame(7, $invoice->fresh()->items[1]['vat_rate']);

        $html = view('invoices.strichlistensystem', ['invoice' => $invoice, 'logo' => '', 'qrCode' => null])->render();
        $this->assertStringContainsString('darin enthaltene 19 % MwSt. (netto 1,68 €)', $html);
        $this->assertStringContainsString('darin enthaltene 7 % MwSt. (netto 1,40 €)', $html);
        $this->assertStringNotContainsString('zzgl.', $html);
        $this->assertStringContainsString('Steuernummer', $html);
    }

    public function test_sender_line_keeps_multibyte_characters_at_the_end(): void
    {
        $this->saveSettings(['club_city' => 'Bratislavà']);
        $user = $this->payer();
        $this->book($user, $this->beer);
        $invoice = $this->createInvoice($user);

        $html = view('invoices.strichlistensystem', ['invoice' => $invoice, 'logo' => '', 'qrCode' => null])->render();

        $this->assertStringContainsString("Fachschaftsrat 5 e.V. · Eupener Straße 70, 52066\u{00A0}Bratislavà</div>", $html);
    }

    public function test_payment_qr_code_contains_the_transfer(): void
    {
        $this->assertSame(
            "BCD\n002\n1\nSCT\nCOBADEFFXXX\nFachschaftsrat 5 e.V.\nDE89370400440532013000\nEUR18.50\n\n\nGER26-0007 Anna Müller",
            EpcQrCode::payload('Fachschaftsrat 5 e.V.', 'DE89 3704 0044 0532 0130 00', 'cobadeffxxx', 1850, "GER26-0007\nAnna Müller"),
        );

        // the bic is optional, too long texts are shortened
        $payload = explode("\n", EpcQrCode::payload(str_repeat('a', 80), 'DE89370400440532013000', null, 1, str_repeat('b', 200)));
        $this->assertSame('', $payload[4]);
        $this->assertSame(70, mb_strlen($payload[5]));
        $this->assertSame('EUR0.01', $payload[7]);
        $this->assertSame(140, mb_strlen($payload[10]));

        $this->expectException(InvalidArgumentException::class);
        EpcQrCode::payload('Verein', 'DE89370400440532013000', null, 0, 'Test');
    }

    public function test_iban_check(): void
    {
        $this->assertTrue(StrichlistensystemInvoiceSettings::isValidIban('DE89 3704 0044 0532 0130 00'));
        $this->assertTrue(StrichlistensystemInvoiceSettings::isValidIban('de89370400440532013000'));
        $this->assertFalse(StrichlistensystemInvoiceSettings::isValidIban('DE89370400440532013001'));
        $this->assertFalse(StrichlistensystemInvoiceSettings::isValidIban('kein iban'));
        $this->assertSame('DE89 3704 0044 0532 0130 00', StrichlistensystemInvoiceSettings::formatIban('de89370400440532013000'));
    }
}
