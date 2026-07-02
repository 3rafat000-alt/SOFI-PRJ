<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\PayrollBatch;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll = new PayrollService()) {}

    public function index(Request $request)
    {
        $company = $request->attributes->get('company');

        $batches = PayrollBatch::where('company_id', $company->id)
            ->latest()->paginate(20);

        return view('company.payroll.index', compact('company', 'batches'));
    }

    public function create(Request $request)
    {
        $company = $request->attributes->get('company');

        $employees = CompanyEmployee::where('company_id', $company->id)
            ->where('is_active', true)->orderBy('name')->get();

        return view('company.payroll.create', compact('company', 'employees'));
    }

    public function store(Request $request)
    {
        $company = $request->attributes->get('company');

        $data = $request->validate([
            'currency' => 'required|in:USD,SYP',
            'title' => 'nullable|string|max:120',
            'sel' => 'required|array|min:1',          // selected employee phones
            'sel.*' => 'string|max:30',
            'amt' => 'required|array',                 // phone => amount map
            'amt.*' => 'nullable|numeric|min:0',
            'nm' => 'nullable|array',                  // phone => name map (optional)
        ], [
            'sel.required' => 'اختر موظفاً واحداً على الأقل.',
        ]);

        // Build payroll rows from the selected roster entries. Unselected
        // employees and zero/blank amounts are dropped (createBatch also re-normalizes).
        $rows = [];
        foreach ($data['sel'] as $phone) {
            $amount = (float) ($data['amt'][$phone] ?? 0);
            if ($amount <= 0) {
                continue;
            }
            $rows[] = [
                'phone' => $phone,
                'amount' => $amount,
                'name' => $data['nm'][$phone] ?? null,
            ];
        }

        if (empty($rows)) {
            return back()->withErrors(['sel' => 'لا توجد مبالغ صالحة للموظفين المختارين.'])->withInput();
        }

        try {
            $batch = $this->payroll->createBatch(
                $company,
                $data['currency'],
                $rows,
                $request->user(),
                idempotencyKey: $request->input('idempotency_key'),
                title: $data['title'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['sel' => $e->getMessage()])->withInput();
        }

        return redirect()->route('company.payroll.show', $batch)
            ->with('success', 'تم إنشاء دفعة الرواتب. راجعها ثم نفّذها.');
    }

    public function show(Request $request, PayrollBatch $batch)
    {
        $company = $request->attributes->get('company');
        abort_unless($batch->company_id === $company->id, 403); // IDOR guard

        $batch->load('items');

        return view('company.payroll.show', compact('company', 'batch'));
    }

    public function run(Request $request, PayrollBatch $batch)
    {
        $company = $request->attributes->get('company');
        abort_unless($batch->company_id === $company->id, 403); // IDOR guard

        if (!$company->canRunPayroll()) {
            return back()->withErrors(['batch' => 'الشركة غير مفعّلة لتوزيع الرواتب بعد. أكمل التحقق أولاً.']);
        }

        try {
            $this->payroll->dispatchBatch($batch);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['batch' => $e->getMessage()]);
        }

        return redirect()->route('company.payroll.show', $batch)
            ->with('success', 'تم تنفيذ دفعة الرواتب.');
    }
}
