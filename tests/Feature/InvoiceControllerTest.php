<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->create(['user_id' => $this->user->id, 'gst_registered' => true]);
        $this->business->seedDefaultChartOfAccounts();
    }

    private function otherBusinessInvoice(): Invoice
    {
        $other = Business::factory()->create();
        $other->seedDefaultChartOfAccounts();
        $customer = $other->customers()->create(['name' => 'Someone Else']);

        return Invoice::factory()->create(['business_id' => $other->id, 'customer_id' => $customer->id]);
    }

    public function test_a_user_cannot_view_another_business_invoice(): void
    {
        $invoice = $this->otherBusinessInvoice();

        $this->actingAs($this->user)->get(route('invoices.show', $invoice))->assertNotFound();
    }

    public function test_a_user_cannot_send_another_business_invoice(): void
    {
        $invoice = $this->otherBusinessInvoice();

        $this->actingAs($this->user)->post(route('invoices.send', $invoice))->assertNotFound();

        $this->assertSame('draft', $invoice->fresh()->status);
        $this->assertSame(0, Transaction::count());
    }

    public function test_an_invoice_cannot_be_raised_against_another_business_customer(): void
    {
        $foreignCustomer = $this->otherBusinessInvoice()->customer;

        $this->actingAs($this->user)->post(route('invoices.store'), [
            'customer_id' => $foreignCustomer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'lines' => [['description' => 'Work', 'quantity' => 1, 'unit_price' => 100, 'gst_applicable' => true]],
        ])->assertSessionHasErrors('customer_id');
    }

    public function test_invoices_get_sequential_numbers_and_correct_totals(): void
    {
        $customer = $this->business->customers()->create(['name' => 'Kai Co']);
        $payload = [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'lines' => [
                ['description' => 'Pies', 'quantity' => 3, 'unit_price' => 3.33, 'gst_applicable' => true],
                ['description' => 'Tip', 'quantity' => 1, 'unit_price' => 5, 'gst_applicable' => false],
            ],
        ];

        $this->actingAs($this->user)->post(route('invoices.store'), $payload)->assertRedirect();
        $this->actingAs($this->user)->post(route('invoices.store'), $payload)->assertRedirect();

        $this->assertSame(['INV-0001', 'INV-0002'], $this->business->invoices()->orderBy('id')->pluck('number')->all());

        $invoice = $this->business->invoices()->first();
        $this->assertEquals(14.99, $invoice->subtotal); // 9.99 + 5.00
        $this->assertEquals(1.50, $invoice->gst_total);  // 15% of 9.99, rounded once
        $this->assertEquals(16.49, $invoice->total);
        $this->assertEquals(9.99, $invoice->lines()->first()->line_total);
    }

    public function test_sending_twice_posts_to_the_ledger_only_once(): void
    {
        $customer = $this->business->customers()->create(['name' => 'Kai Co']);
        $invoice = Invoice::factory()->create(['business_id' => $this->business->id, 'customer_id' => $customer->id]);
        $invoice->lines()->create(['description' => 'Work', 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100]);
        $invoice->load('lines')->recalculateTotals();
        $invoice->save();

        $this->actingAs($this->user)->post(route('invoices.send', $invoice))->assertRedirect();
        $this->actingAs($this->user)->post(route('invoices.send', $invoice))->assertStatus(422);

        $this->assertSame(1, Transaction::count());
        $this->assertSame('sent', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->transaction_id);
    }

    public function test_duplicate_account_code_is_a_validation_error_not_a_500(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounts.store'), ['code' => '1000', 'name' => 'Dup', 'type' => 'asset'])
            ->assertSessionHasErrors('code');
    }

    public function test_pages_render(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->user)->get(route('accounts.index'))->assertOk();
        $this->actingAs($this->user)->get(route('invoices.index'))->assertOk();
        $this->actingAs($this->user)->get(route('invoices.create'))->assertOk();
    }
}
