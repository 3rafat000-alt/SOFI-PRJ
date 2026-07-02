<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\CCPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CCPaymentWebhookController extends Controller
{
    /** CCPayment "add webhook URL" activation handshake type. */
    private const ACTIVATE_TYPE = 'ActivateWebhookURL';

    private CCPaymentService $ccpayment;

    public function __construct(CCPaymentService $ccpayment)
    {
        $this->ccpayment = $ccpayment;
    }

    /**
     * Handle deposit webhook from CCPayment
     * POST /webhooks/ccpayment/deposit
     */
    public function deposit(Request $request): JsonResponse
    {
        Log::info('CCPayment deposit webhook received', [
            'ip' => $request->ip(),
            'type' => $request->input('type'),
            'body' => $request->all(),
        ]);

        // Verify IP whitelist
        if (!$this->verifyIp($request)) {
            Log::warning('CCPayment deposit webhook: IP rejected', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => 'IP not allowed'], 403);
        }

        // Verify signature
        if (!$this->verifySignature($request)) {
            Log::warning('CCPayment deposit webhook: Invalid signature');
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        // CCPayment "Add Webhook URL" activation handshake (signed POST, type=ActivateWebhookURL).
        if ($activation = $this->handleActivation($request)) {
            return $activation;
        }

        try {
            $this->ccpayment->handleDepositWebhook($request->all());
            return response()->json(['success' => true, 'message' => 'تم المعالجة']);
        } catch (\Exception $e) {
            Log::error('CCPayment deposit webhook error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطأ في المعالجة'], 500);
        }
    }

    /**
     * Handle withdrawal webhook from CCPayment
     * POST /webhooks/ccpayment/withdraw
     */
    public function withdraw(Request $request): JsonResponse
    {
        Log::info('CCPayment withdraw webhook received', [
            'ip' => $request->ip(),
            'type' => $request->input('type'),
            'body' => $request->all(),
        ]);

        // Verify IP whitelist
        if (!$this->verifyIp($request)) {
            Log::warning('CCPayment withdraw webhook: IP rejected', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => 'IP not allowed'], 403);
        }

        // Verify signature
        if (!$this->verifySignature($request)) {
            Log::warning('CCPayment withdraw webhook: Invalid signature');
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        // CCPayment "Add Webhook URL" activation handshake (signed POST, type=ActivateWebhookURL).
        if ($activation = $this->handleActivation($request)) {
            return $activation;
        }

        try {
            $this->ccpayment->handleWithdrawWebhook($request->all());
            return response()->json(['success' => true, 'message' => 'تم المعالجة']);
        } catch (\Exception $e) {
            Log::error('CCPayment withdraw webhook error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطأ في المعالجة'], 500);
        }
    }

    /**
     * Handle the CCPayment "Add Webhook URL" activation handshake.
     *
     * When a webhook URL is registered in the CCPayment dashboard, CCPayment POSTs
     * a signed {"type":"ActivateWebhookURL","msg":{}} probe. The endpoint must reply
     * 200 with a JSON body containing the string "Success" to confirm activation.
     * Signature is already verified by the caller before this runs.
     *
     * @return JsonResponse|null  Success response when this is an activation probe, else null.
     */
    private function handleActivation(Request $request): ?JsonResponse
    {
        if (($request->input('type') ?? null) !== self::ACTIVATE_TYPE) {
            return null;
        }

        Log::info('CCPayment webhook activation handshake confirmed', ['ip' => $request->ip()]);

        return response()->json(['msg' => 'Success']);
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $sign = $request->header('Sign');
        $timestamp = $request->header('Timestamp');
        $body = $request->getContent();

        if (!$sign || !$timestamp) {
            return false;
        }

        return $this->ccpayment->verifyWebhookSignature($body, $sign, $timestamp);
    }

    /**
     * Verify webhook IP address
     */
    private function verifyIp(Request $request): bool
    {
        $ip = $request->ip();
        return $this->ccpayment->verifyWebhookIp($ip);
    }

    /**
     * Test endpoint: Simulate CCPayment deposit webhook
     * POST /webhooks/ccpayment/test/deposit
     * 
     * For development/testing only - requires authentication
     */
    public function testDeposit(Request $request): JsonResponse
    {
        // Only allow in local/debug environments
        if (!app()->environment(['local', 'development', 'testing'])) {
            return response()->json(['success' => false, 'message' => 'Test endpoint not available in production'], 403);
        }

        $validated = $request->validate([
            'referenceId' => 'required|string',
            'status' => 'required|in:success,failed,pending',
            'amount' => 'required|string',
            'wallet_id' => 'nullable|integer|exists:wallets,id',
        ]);

        try {
            $testData = $this->ccpayment->generateTestWebhookPayload('deposit', [
                'referenceId' => $validated['referenceId'],
                'status' => $validated['status'],
                'amount' => $validated['amount'],
            ]);

            // Process the webhook directly
            $this->ccpayment->handleDepositWebhook($testData['payload']);

            Log::info('CCPayment test deposit webhook processed', [
                'reference' => $validated['referenceId'],
                'status' => $validated['status'],
                'amount' => $validated['amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test deposit webhook processed',
                'payload' => $testData['payload'],
                'headers' => $testData['headers'],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment test deposit error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test endpoint: Simulate CCPayment withdrawal webhook
     * POST /webhooks/ccpayment/test/withdraw
     * 
     * For development/testing only - requires authentication
     */
    public function testWithdraw(Request $request): JsonResponse
    {
        // Only allow in local/debug environments
        if (!app()->environment(['local', 'development', 'testing'])) {
            return response()->json(['success' => false, 'message' => 'Test endpoint not available in production'], 403);
        }

        $validated = $request->validate([
            'orderId' => 'required|string',
            'status' => 'required|in:success,failed,pending',
            'amount' => 'required|string',
        ]);

        try {
            $testData = $this->ccpayment->generateTestWebhookPayload('withdraw', [
                'orderId' => $validated['orderId'],
                'status' => $validated['status'],
                'amount' => $validated['amount'],
            ]);

            // Process the webhook directly
            $this->ccpayment->handleWithdrawWebhook($testData['payload']);

            Log::info('CCPayment test withdraw webhook processed', [
                'orderId' => $validated['orderId'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test withdraw webhook processed',
                'payload' => $testData['payload'],
                'headers' => $testData['headers'],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment test withdraw error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get webhook configuration info
     * GET /webhooks/ccpayment/info
     */
    public function info(): JsonResponse
    {
        if (!app()->environment(['local', 'development', 'testing'])) {
            return response()->json(['success' => false, 'message' => 'Info endpoint not available in production'], 403);
        }

        // Webhook host is decoupled from APP_URL (branded sakk.app) so callbacks
        // hit the live reachable host (sakk.zanjour.com). See services.ccpayment.webhook_base.
        $appUrl = config('services.ccpayment.webhook_base', config('app.url', 'http://localhost:8000'));

        return response()->json([
            'success' => true,
            'webhooks' => [
                'deposit' => $appUrl . '/webhooks/ccpayment/deposit',
                'withdraw' => $appUrl . '/webhooks/ccpayment/withdraw',
            ],
            'test_endpoints' => [
                'deposit_test' => $appUrl . '/webhooks/ccpayment/test/deposit',
                'withdraw_test' => $appUrl . '/webhooks/ccpayment/test/withdraw',
            ],
            'ngrok_instructions' => [
                'install' => 'npm install -g ngrok',
                'run' => 'ngrok http 8000',
                'configure_dashboard' => 'Use the HTTPS URL from ngrok output',
            ],
        ]);
    }
}
