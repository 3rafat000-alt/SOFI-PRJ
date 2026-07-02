<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\PayrollBatch;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->attributes->get('company');

        $wallets = $company->wallets()->get()->keyBy('currency');
        $employeeCount = CompanyEmployee::where('company_id', $company->id)->where('is_active', true)->count();
        $recentBatches = PayrollBatch::where('company_id', $company->id)
            ->latest()->limit(5)->get();

        return view('company.dashboard', [
            'company' => $company,
            'wallets' => $wallets,
            'employeeCount' => $employeeCount,
            'recentBatches' => $recentBatches,
        ]);
    }
}
