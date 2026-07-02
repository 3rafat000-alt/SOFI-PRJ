<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SAKK Payment Gateway Service — Real SAKK API Integration
 *
 * Calls SAKK Wallet (sakk.zanjour.com) Payment Requests API:
 *   POST /api/v1/payment-requests  →  create checkout
 *   Auth: Bearer {sanctum_token}
 *
 * Flow:
 *   1. createPayment() → calls SAKK API, returns pay_url (agency opens in SAKK app)
 *   2. Agency pays via SAKK app
 *   3. SAKK fires webhook → event payment_request.paid
 *   4. Subscription activated
 */
class SakkService
{
    private string $baseUrl;
    private string $apiToken;       // Sanctum token (user auth), not merchant api_key
    private string $webhookSecret;  // callback_secret for HMAC signing
    private string $payUrlBase;     // base for redirect URL
    private string $callbackUrl;    // where SAKK sends webhook

    public function __construct()
    {
        // Read settings from DB — keys stored via admin UI
        $this->apiToken      = Setting::where('key', 'sakk_api_key')->value('value') ?? '';
        $this->webhookSecret = Setting::where('key', 'sakk_webhook_secret')->value('value') ?? '';
        $sandbox             = (Setting::where('key', 'sakk_sandbox')->value('value') ?? 'true') === 'true';

        // Real SAKK API endpoints
        $this->baseUrl     = 'https://sakk.zanjour.com/api/v1';
        $this->payUrlBase  = 'https://sakk.zanjour.com/pay';
        $this->callbackUrl = route('sakk.webhook');
    }

    /**
     * Check if SAKK is configured (has token)
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken);
    }

    /**
     * Create payment request on SAKK — agency pays via SAKK app
     *
     * Calls POST /api/v1/payment-requests (Sanctum-authenticated).
     * SAKK returns uuid → stored as transaction_id.
     * Customer opens pay_url in SAKK app to complete payment.
     *
     * @param  array{amount: float, currency: string, description: string, callback_url: string, reference_id: string, customer?: array}
     * @return array{success: bool, payment_url?: string, transaction_id?: string, error?: string}
     */
    public function createPayment(array $data): array
    {
        if (!$this->isConfigured()) {
            return $this->mockPayment($data);
        }

        try {
            $response = Http::timeout(15)
                ->withToken($this->apiToken)
                ->post("{$this->baseUrl}/payment-requests", [
                    'amount'           => $data['amount'],
                    'currency'         => $data['currency'] ?? 'USD',
                    'note'             => $data['description'] ?? '',
                    'expires_in_hours' => 72,
                    'callback_url'     => $this->callbackUrl,
                    'callback_secret'  => $this->webhookSecret,
                ]);

            if ($response->successful()) {
                $body = $response->json('data', []);
                $uuid = $body['uuid'] ?? '';

                if (empty($uuid)) {
                    Log::error('SAKK createPayment: empty uuid in response', ['body' => $response->json()]);
                    return ['success' => false, 'error' => 'Invalid response from SAKK'];
                }

                return [
                    'success'        => true,
                    'payment_url'    => $this->payUrlBase . '/' . $uuid,
                    'transaction_id' => $uuid,
                ];
            }

            Log::error('SAKK createPayment failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'error'   => $response->json('message') ?? 'Payment gateway error',
            ];
        } catch (\Throwable $e) {
            Log::error('SAKK createPayment exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error'   => 'Payment service unavailable',
            ];
        }
    }

    /**
     * Verify webhook HMAC-SHA256 signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Check payment request status from SAKK API
     */
    public function getPaymentStatus(string $uuid): array
    {
        if (!$this->isConfigured()) {
            return ['success' => true, 'status' => 'completed'];
        }

        try {
            $response = Http::timeout(10)
                ->withToken($this->apiToken)
                ->get("{$this->baseUrl}/payment-requests/{$uuid}");

            if ($response->successful()) {
                $data = $response->json('data', []);
                return [
                    'success' => true,
                    'status'  => $data['status'] ?? 'unknown',
                    'data'    => $data,
                ];
            }

            return ['success' => false, 'error' => 'Cannot verify payment'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fallback: mock payment when no SAKK token configured (dev mode)
     * Shows internal checkout page that simulates SAKK payment flow
     */
    private function mockPayment(array $data): array
    {
        $tid = 'SAKK-' . strtoupper(bin2hex(random_bytes(8)));

        Log::info('SAKK mock payment (no token configured)', [
            'reference_id' => $data['reference_id'],
            'amount'       => $data['amount'],
            'transaction'  => $tid,
        ]);

        return [
            'success'        => true,
            'payment_url'    => route('sakk.mock-checkout', [
                'transaction_id' => $tid,
                'reference_id'   => $data['reference_id'],
                'amount'         => $data['amount'],
                'currency'       => $data['currency'] ?? 'USD',
            ]),
            'transaction_id' => $tid,
            '_mock'          => true,
        ];
    }
}
