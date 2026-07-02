<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Support\PhoneNormalizer;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->attributes->get('company');

        $employees = CompanyEmployee::where('company_id', $company->id)
            ->orderByDesc('id')
            ->paginate(30);

        return view('company.employees.index', compact('company', 'employees'));
    }

    public function store(Request $request)
    {
        $company = $request->attributes->get('company');

        $data = $request->validate([
            'name' => 'nullable|string|max:120',
            'phone' => 'required|string|max:30',
            'job_title' => 'nullable|string|max:120',
            'default_amount' => 'nullable|numeric|min:0',
            'default_currency' => 'nullable|in:USD,SYP',
        ]);

        $phone = PhoneNormalizer::canonical($data['phone']);
        if ($phone === '') {
            return back()->withErrors(['phone' => 'رقم الهاتف غير صالح.'])->withInput();
        }

        CompanyEmployee::updateOrCreate(
            ['company_id' => $company->id, 'phone' => $phone],
            [
                'name' => $data['name'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'default_amount' => $data['default_amount'] ?? null,
                'default_currency' => $data['default_currency'] ?? 'USD',
                'is_active' => true,
            ],
        );

        return back()->with('success', 'تمت إضافة الموظف.');
    }

    public function destroy(Request $request, CompanyEmployee $employee)
    {
        $company = $request->attributes->get('company');
        // IDOR guard: only the owning company may delete.
        abort_unless($employee->company_id === $company->id, 403);

        $employee->delete();

        return back()->with('success', 'تم حذف الموظف.');
    }

    /** Bulk import employees from a CSV: phone, name, amount[, currency]. */
    public function import(Request $request)
    {
        $company = $request->attributes->get('company');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => 'تعذّر قراءة الملف.']);
        }

        $imported = 0;
        $skipped = 0;
        $row = 0;
        while (($cols = fgetcsv($handle)) !== false) {
            $row++;
            // Skip a header row if the first cell isn't a phone-ish value.
            if ($row === 1 && !preg_match('/\d/', (string) ($cols[0] ?? ''))) {
                continue;
            }

            $phone = PhoneNormalizer::canonical($cols[0] ?? '');
            $name = isset($cols[1]) ? trim((string) $cols[1]) : null;
            $amount = isset($cols[2]) ? (float) preg_replace('/[^\d.]/', '', (string) $cols[2]) : null;
            $currency = isset($cols[3]) && strtoupper(trim($cols[3])) === 'SYP' ? 'SYP' : 'USD';

            if ($phone === '') {
                $skipped++;
                continue;
            }

            CompanyEmployee::updateOrCreate(
                ['company_id' => $company->id, 'phone' => $phone],
                [
                    'name' => $name,
                    'default_amount' => $amount,
                    'default_currency' => $currency,
                    'is_active' => true,
                ],
            );
            $imported++;
        }
        fclose($handle);

        return back()->with('success', "تم استيراد {$imported} موظف، وتم تخطّي {$skipped} صف.");
    }
}
