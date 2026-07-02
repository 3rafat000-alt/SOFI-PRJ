<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Fee Management Controller
 * 
 * Manages all platform fees from admin dashboard
 */
class FeeController extends Controller
{
    public function __construct(
        private FeeService $feeService
    ) {}

    /**
     * Display fee management page
     */
    public function index()
    {
        $fees = $this->feeService->getAllFeesGrouped();
        $feePreview = $this->feeService->getFeePreview(100);

        return view('admin.fees.index', [
            'fees' => $fees,
            'feePreview' => $feePreview,
            'feeTypes' => [
                'deposit' => 'رسوم الإيداع',
                'withdrawal' => 'رسوم السحب',
                'card_fund' => 'رسوم البطاقات',
                'exchange' => 'رسوم الذهب',
                'transfer' => 'رسوم التحويلات',
                'p2p' => 'رسوم تحويل P2P',
                'partner' => 'الوكلاء والتجار',
            ],
        ]);
    }

    /**
     * Update a specific fee
     */
    public function update(Request $request, string $code)
    {
        $fee = Fee::where('code', $code)->first();

        if (!$fee) {
            return back()->with('error', 'الرسم غير موجود');
        }

        $validator = Validator::make($request->all(), [
            'fee_type' => 'nullable|in:fixed,percentage',
            'fixed_amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'max_fee' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active');
        
        // Keep existing values for fields not present in the simplified form
        // at all. Use array_key_exists (not ??=) for max_fee/max_amount: those
        // two are allowed to be explicitly submitted empty/null to CLEAR a
        // previously-set limit, and ??= would silently restore the old value
        // before the "empty -> null" clearing logic below ever runs.
        $data['name_ar'] ??= $fee->name_ar;
        $data['name_en'] ??= $fee->name_en;
        $data['min_fee'] ??= $fee->min_fee;
        $data['min_amount'] ??= $fee->min_amount;
        if (!array_key_exists('max_fee', $data)) {
            $data['max_fee'] = $fee->max_fee;
        }
        if (!array_key_exists('max_amount', $data)) {
            $data['max_amount'] = $fee->max_amount;
        }
        
        // Handle fee type: either fixed OR percentage (not both)
        $feeType = $request->input('fee_type', $fee->percentage > 0 ? 'percentage' : 'fixed');
        if ($feeType === 'fixed') {
            $data['fixed_amount'] = $request->input('fixed_amount', $fee->fixed_amount);
            $data['percentage'] = 0;
        } else {
            $data['fixed_amount'] = 0;
            $data['percentage'] = $request->input('percentage', $fee->percentage);
        }
        
        // Handle empty max values
        if (empty($data['max_fee'])) {
            $data['max_fee'] = null;
        }
        if (empty($data['max_amount'])) {
            $data['max_amount'] = null;
        }

        $this->feeService->updateFee($code, $data);

        return back()->with('success', "تم تحديث رسوم \"{$fee->name_ar}\" بنجاح");
    }

    /**
     * Toggle fee active status
     */
    public function toggle(string $code)
    {
        $fee = $this->feeService->toggleFeeStatus($code);

        if (!$fee) {
            return back()->with('error', 'الرسم غير موجود');
        }

        $status = $fee->is_active ? 'مفعّل' : 'معطّل';
        return back()->with('success', "تم تغيير حالة \"{$fee->name_ar}\" إلى {$status}");
    }

    /**
     * Preview fee calculation
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:fees,code',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $result = $this->feeService->calculateFee(
            $request->input('code'),
            $request->input('amount')
        );

        return response()->json($result);
    }

    /**
     * Get all fees as JSON (for API)
     */
    public function apiIndex()
    {
        $fees = Fee::active()->orderBy('type')->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $fees->map(fn($fee) => [
                'code' => $fee->code,
                'name_ar' => $fee->name_ar,
                'name_en' => $fee->name_en,
                'type' => $fee->type,
                'currency' => $fee->currency,
                'fixed_amount' => $fee->fixed_amount,
                'percentage' => $fee->percentage,
                'min_fee' => $fee->min_fee,
                'max_fee' => $fee->max_fee,
                'min_amount' => $fee->min_amount,
                'max_amount' => $fee->max_amount,
            ]),
        ]);
    }

    /**
     * Calculate fee via API
     */
    public function apiCalculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $result = $this->feeService->calculateFee(
            $request->input('code'),
            $request->input('amount')
        );

        return response()->json($result);
    }
}
