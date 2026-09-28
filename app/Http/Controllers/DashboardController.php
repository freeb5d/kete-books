<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $accounts = $business->accounts()->where('is_active', true)->withBalanceTotals()->get();

        $totalIncome = $accounts->where('type', 'income')->sum(fn ($a) => $a->balance());
        $totalExpense = $accounts->where('type', 'expense')->sum(fn ($a) => $a->balance());
        $cashBalance = $accounts->whereIn('code', ['1000', '1010'])->sum(fn ($a) => $a->balance());
        $receivable = $accounts->where('code', '1200')->first()?->balance() ?? 0;
        $gstPayable = $accounts->where('code', '2100')->first()?->balance() ?? 0;

        $overdueInvoices = $business->invoices()
            ->where('status', 'sent')
            ->whereDate('due_date', '<', now()->toDateString())
            ->with('customer')
            ->get();

        return view('dashboard.index', [
            'business' => $business,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalIncome - $totalExpense,
            'cashBalance' => $cashBalance,
            'receivable' => $receivable,
            'gstPayable' => $gstPayable,
            'overdueInvoices' => $overdueInvoices,
        ]);
    }
}
