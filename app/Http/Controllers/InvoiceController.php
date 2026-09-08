<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\LedgerService;
use Illuminate\Http\Request;

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
        $customers = $request->user()->currentBusiness->customers;

        return view('invoices.create', ['customers' => $customers]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.gst_applicable' => ['boolean'],
        ]);

        $business = $request->user()->currentBusiness;

        $invoice = $business->invoices()->create([
            'customer_id' => $validated['customer_id'],
            'number' => $this->nextInvoiceNumber($business),
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
                'line_total' => $line['quantity'] * $line['unit_price'],
            ]);
        }

        $invoice->load('lines');
        $invoice->recalculateTotals();
        $invoice->save();

        return redirect()->route('invoices.show', $invoice)
            ->with('status', __('invoices.created_successfully'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['lines', 'customer', 'business']);

        return view('invoices.show', ['invoice' => $invoice]);
    }

    /**
     * Marks a draft invoice as sent and posts the journal entry to the ledger.
     * This is the moment the invoice becomes "real" bookkeeping — before this
     * it's just a draft with no accounting impact.
     */
    public function send(Invoice $invoice)
    {
        abort_if($invoice->status !== 'draft', 422, __('invoices.only_draft_can_be_sent'));

        $transaction = $this->ledger->postInvoiceIssued($invoice);

        $invoice->update([
            'status' => 'sent',
            'transaction_id' => $transaction->id,
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('status', __('invoices.sent_successfully'));
    }

    private function nextInvoiceNumber($business): string
    {
        $count = $business->invoices()->count() + 1;

        return 'INV-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
