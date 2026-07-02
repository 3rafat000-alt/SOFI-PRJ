<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\CompanyService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private CompanyService $companies = new CompanyService()) {}

    public function index(Request $request)
    {
        $company = $request->attributes->get('company');

        $wallets = $company->wallets()->get()->keyBy('currency');
        $transactions = Transaction::where('company_id', $company->id)
            ->latest()->limit(40)->get();

        return view('company.wallet.index', compact('company', 'wallets', 'transactions'));
    }

    /** Prefund the company wallet from the operator's personal balance. */
    public function topup(Request $request)
    {
        $company = $request->attributes->get('company');

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
        ]);

        try {
            $this->companies->topUpFromOperator(
                $company,
                $request->user(),
                (float) $data['amount'],
                $data['currency'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'تم شحن محفظة الشركة بنجاح.');
    }
}
