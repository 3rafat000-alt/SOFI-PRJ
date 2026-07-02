<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayrollBatch;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\PartnerApprovalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query()->with('bankAccount')->withCount('employees')->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('company_code', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }
        if (($status = $request->get('status')) !== null && $status !== '') {
            match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'payroll' => $query->where('payroll_enabled', true),
                'pending' => $query->where('kyc_status', 'pending'),
                default => null,
            };
        }

        $companies = $query->paginate(20)->withQueryString();

        $isFragment = $request->ajax() || $request->boolean('fragment');
        if ($isFragment) {
            return view('admin.companies.partials._table', compact('companies'))->render();
        }

        $stats = [
            'total'           => Company::count(),
            'active'          => Company::where('is_active', true)->count(),
            'verified'        => Company::where('is_verified', true)->count(),
            'payroll_enabled' => Company::where('payroll_enabled', true)->count(),
            'pending_kyc'     => Company::where('kyc_status', 'pending')->count(),
        ];

        return view('admin.companies.index', compact('companies', 'stats'));
    }

    public function kpis(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'total'           => Company::count(),
            'active'          => Company::where('is_active', true)->count(),
            'verified'        => Company::where('is_verified', true)->count(),
            'payroll_enabled' => Company::where('payroll_enabled', true)->count(),
            'pending_kyc'     => Company::where('kyc_status', 'pending')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'legal_name' => 'nullable|string|max:160',
            'owner_name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:160',
            'phone' => 'nullable|string|max:30',
            'tax_id' => 'nullable|string|max:60',
            'commercial_register' => 'nullable|string|max:60',
            'city' => 'nullable|string|max:80',
            'operator_email' => 'nullable|email|max:160', // link a portal operator
            'payroll_enabled' => 'nullable|boolean',
        ]);

        // Optionally link the SAKK user who will operate the portal.
        $operator = null;
        if (!empty($validated['operator_email'])) {
            $operator = \App\Models\User::whereRaw('LOWER(email) = ?', [strtolower($validated['operator_email'])])->first();
            if (!$operator) {
                return back()->withErrors(['operator_email' => 'لا يوجد مستخدم بهذا البريد.'])->withInput();
            }
            if (Company::where('user_id', $operator->id)->exists()) {
                return back()->withErrors(['operator_email' => 'هذا المستخدم يملك شركة بالفعل.'])->withInput();
            }
        }

        $company = Company::create([
            'user_id' => $operator?->id,
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? null,
            'owner_name' => $validated['owner_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'commercial_register' => $validated['commercial_register'] ?? null,
            'city' => $validated['city'] ?? null,
            'is_active' => true,
            'kyc_status' => 'pending',
            'kyc_submitted_at' => now(),
        ]);

        // payroll_enabled is guarded (SEC-003) — admin may enable on create via forceFill.
        if ($request->boolean('payroll_enabled')) {
            $company->forceFill([
                'payroll_enabled' => true,
                'is_verified' => true,
                'kyc_status' => 'approved',
                'kyc_approved_at' => now(),
            ])->save();

            (new PartnerApprovalNotifier())->notifyCompany($company->fresh());
        }

        return redirect()->route('admin.companies.show', $company)->with('success', 'تم إنشاء الشركة');
    }

    public function show(Company $company): View
    {
        $company->loadCount('employees');
        $wallets = $company->wallets()->get()->keyBy('currency');
        $batches = PayrollBatch::where('company_id', $company->id)->latest()->limit(10)->get();

        return view('admin.companies.show', compact('company', 'wallets', 'batches'));
    }

    public function edit(Company $company): View
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'legal_name' => 'nullable|string|max:160',
            'owner_name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:160',
            'phone' => 'nullable|string|max:30',
            'tax_id' => 'nullable|string|max:60',
            'commercial_register' => 'nullable|string|max:60',
            'city' => 'nullable|string|max:80',
            'is_active' => 'nullable|boolean',
            'payroll_enabled' => 'nullable|boolean',
        ]);

        $company->update([
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? null,
            'owner_name' => $validated['owner_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'commercial_register' => $validated['commercial_register'] ?? null,
            'city' => $validated['city'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        // payroll_enabled is guarded (SEC-003) — set via forceFill only here.
        $company->forceFill(['payroll_enabled' => $request->boolean('payroll_enabled')])->save();

        // Notify the company operator on first approval via the update form.
        if ($request->boolean('payroll_enabled')) {
            (new PartnerApprovalNotifier())->notifyCompany($company->fresh());
        }

        return redirect()->route('admin.companies.show', $company)->with('success', 'تم تحديث الشركة');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', 'تم حذف الشركة');
    }

    /** Admin prefunds a company wallet directly (ledger adjustment). */
    public function toggleStatus(Request $request, Company $company): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason'    => ['nullable', 'string', 'max:500'],
        ]);

        $company->forceFill(['is_active' => $validated['is_active']])->save();

        return response()->json([
            'success' => true,
            'message' => $validated['is_active'] ? 'تم تفعيل الشركة' : 'تم إيقاف الشركة',
            'is_active' => $validated['is_active'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Company::query()->with('bankAccount')->withCount('employees');

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('company_code', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }
        if (($status = $request->get('status')) !== null && $status !== '') {
            match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'payroll' => $query->where('payroll_enabled', true),
                'pending' => $query->where('kyc_status', 'pending'),
                default => null,
            };
        }

        \App\Models\ActivityLog::log('companies.export', null, null, null, null, 'Admin exported companies CSV');

        $filename = 'companies_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'company_code', 'name', 'phone',
                'is_active', 'is_verified', 'kyc_status',
                'payroll_enabled', 'employees_count',
                'bank_name', 'bank_account_last4', 'created_at',
            ]);
            $query->chunk(500, function ($companies) use ($handle): void {
                foreach ($companies as $c) {
                    fputcsv($handle, [
                        $c->company_code,
                        $c->name,
                        $c->phone,
                        $c->is_active ? '1' : '0',
                        $c->is_verified ? '1' : '0',
                        $c->kyc_status instanceof \App\Enums\KycStatus ? $c->kyc_status->value : $c->kyc_status,
                        $c->payroll_enabled ? '1' : '0',
                        $c->employees_count,
                        $c->bankAccount?->bank_name,
                        $c->bankAccount?->account_number_last4,
                        $c->created_at?->toDateTimeString(),
                    ]);
                }
            });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function topup(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
        ]);

        DB::transaction(function () use ($company, $validated) {
            $wallet = Wallet::where('company_id', $company->id)
                ->where('currency', $validated['currency'])
                ->lockForUpdate()
                ->first();
            if (!$wallet) {
                $created = $company->companyWallet($validated['currency']);
                $wallet = Wallet::whereKey($created->id)->lockForUpdate()->first();
            }

            $before = (float) $wallet->balance;
            $wallet->credit((float) $validated['amount']);

            Transaction::create([
                'user_id' => $company->user_id ?? auth()->id(),
                'wallet_id' => $wallet->id,
                'company_id' => $company->id,
                'type' => TransactionType::ADJUSTMENT,
                'category' => TransactionCategory::PAYROLL,
                'currency' => $validated['currency'],
                'amount' => (float) $validated['amount'],
                'fee' => 0,
                'net_amount' => (float) $validated['amount'],
                'balance_before' => $before,
                'balance_after' => (float) $wallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'شحن إداري لمحفظة الشركة',
                'metadata' => ['source' => 'admin_topup', 'admin_id' => auth()->id()],
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('admin.companies.show', $company)->with('success', 'تم شحن محفظة الشركة');
    }
}
