<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $request->user()->currentBusiness
            ->accounts()
            ->withBalanceTotals()
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return view('accounts.index', ['accountsByType' => $accounts]);
    }

    public function store(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('accounts', 'code')->where('business_id', $business->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
        ]);

        $business->accounts()->create($validated);

        return back()->with('status', __('accounts.created_successfully'));
    }
}
