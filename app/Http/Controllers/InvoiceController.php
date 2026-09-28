<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function __construct(private LedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $invoices = $request->user()->currentBusiness
            ->invoices()
            ->with('customer')
            ->latest('issue_date')
            ->paginate(20);

        return view('invoices.index', ['invoices' => $invoices]);
    }

    public function create(Request $request)
    {
        $customers = $request->user()->currentBusiness->customers()->orderBy('name')->get();

        return view('invoices.create', ['customers' => $customers]);
    }

    public function store(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('business_id', $business->id)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.gst_applicable' => ['boolean'],
        ]);

        $invoice = DB::transaction(function () use ($business, $validated) {
            $invoice = $business->invoices()->create([
                'customer_id' => $validated['customer_id'],
                'number' => $business->nextInvoiceNumber(),
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'currency' => $business->currency,
            ]);

            foreach ($validated['lines'] as $line) {
                $invoice->lines()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'gst_applicable' => $line['gst_applicable'] ?? true,
                    'line_total' => Invoice::lineTotal($line['quantity'], $line['unit_price']),
                ]);
            }

            $invoice->load('lines');
            $invoice->recalculateTotals();
            $invoice->save();

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('status', __('invoices.created_successfully'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->ensureOwnedByCurrentBusiness($request, $invoice);

        $invoice->load(['lines', 'customer', 'business']);

        return view('invoices.show', ['invoice' => $invoice]);
    }

    /**
     * Marks a draft invoice as sent and posts the journal entry to the ledger.
     * This is the moment the invoice becomes "real" bookkeeping — before this
     * it's just a draft with no accounting impact.
     *
     * The invoice row is locked and the status re-checked inside the same DB
     * transaction as the ledger post, so a double-click can't post twice and
     * a failure can't leave a posted entry against a draft invoice.
     */
    public function send(Request $request, Invoice $invoice)
    {
        $this->ensureOwnedByCurrentBusiness($request, $invoice);

        DB::transaction(function () use ($invoice) {
            $locked = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            abort_if($locked->status !== 'draft', 422, __('invoices.only_draft_can_be_sent'));

            $transaction = $this->ledger->postInvoiceIssued($locked);

            $locked->update([
                'status' => 'sent',
                'transaction_id' => $transaction->id,
            ]);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('status', __('invoices.sent_successfully'));
    }

    /** 404 rather than 403, so invoice IDs of other businesses aren't confirmed to exist. */
    private function ensureOwnedByCurrentBusiness(Request $request, Invoice $invoice): void
    {
        abort_unless((int) $invoice->business_id === (int) $request->user()->currentBusiness?->id, 404);
    }
}
