<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $request->user()->currentBusiness
            ->accounts()
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return view('accounts.index', ['accountsByType' => $accounts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
        ]);

        $request->user()->currentBusiness->accounts()->create($validated);

        return back()->with('status', __('accounts.created_successfully'));
    }
}
