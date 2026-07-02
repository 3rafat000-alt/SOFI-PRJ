<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging sender — HTTP v1 API.
 *
 * The legacy `fcm/send` + server-key API was shut down by Google in June 2024.
 * This service authenticates with a service-account JSON (OAuth2 JWT-bearer,
 * RS256-signed via openssl) and posts to the v1 endpoint.
 *
 * Credentials (Integration 'notifications', with .env fallback):
 *   - fcm_service_account : full service-account JSON string  (SECRET — never commit)
 *   - fcm_project_id      : Firebase project id (defaults to the SA's project_id)
 * .env fallback: FCM_SERVICE_ACCOUNT_FILE (path to the JSON), FCM_PROJECT_ID.
 */
class FCMService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const V1_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    private ?array $serviceAccount = null;
    private ?string $projectId = null;

    public function __construct()
    {
        $integration = Integration::where('key', 'notifications')
            ->where('is_active', true)
            ->first();

        $this->serviceAccount = $this->resolveServiceAccount($integration);
        $this->projectId = $integration?->getCredential('fcm_project_id')
            ?: ($this->serviceAccount['project_id'] ?? null)
            ?: (config('services.fcm.project_id') ?: null);
    }

    public function isConfigured(): bool
    {
        return !empty($this->serviceAccount['client_email'])
            && !empty($this->serviceAccount['private_key'])
            && !empty($this->projectId);
    }

    /**
     * Send to a single device token. Returns true on FCM 200.
     */
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('FCMService: not configured — no service account');
            return false;
        }

        return $this->postMessage($this->buildMessage(['token' => $token], $title, $body, $data), $token);
    }

    /**
     * Send to many tokens. v1 has no multicast envelope — sends per token.
     * Returns the count of successful deliveries.
     */
    public function sendToMultiple(array $tokens, string $title, string $body, array $data = []): int
    {
        if (empty($tokens) || !$this->isConfigured()) {
            return 0;
        }

        $tokens = array_values(array_unique(array_filter($tokens)));
        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return 0;
        }

        $success = 0;
        foreach ($tokens as $token) {
            if ($this->postMessage($this->buildMessage(['token' => $token], $title, $body, $data), $token, $accessToken)) {
                $success++;
            }
        }

        return $success;
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        return $this->postMessage($this->buildMessage(['topic' => $topic], $title, $body, $data), "topic:{$topic}");
    }

    /**
     * Validate credentials end-to-end via a validate_only dry-run.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'حساب خدمة FCM غير مُكوَّن'];
        }

        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return ['success' => false, 'message' => 'فشل توليد رمز OAuth — تحقق من حساب الخدمة'];
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post(sprintf(self::V1_URL, $this->projectId), [
                    'validate_only' => true,
                    'message' => $this->buildMessage(['topic' => 'connection-test'], 'test', 'test', []),
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'اتصال FCM ناجح'];
            }

            $error = $response->json('error.message') ?? 'unknown';
            return ['success' => false, 'message' => "خطأ FCM: {$error}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    // ── internals ───────────────────────────────────────────────────────────

    /**
     * Build a v1 message body. v1 requires all `data` values to be strings.
     */
    private function buildMessage(array $target, string $title, string $body, array $data): array
    {
        $stringData = array_map(
            static fn ($v) => is_scalar($v) ? (string) $v : json_encode($v),
            array_merge($data, ['click_action' => 'FLUTTER_NOTIFICATION_CLICK'])
        );

        return array_merge($target, [
            'notification' => ['title' => $title, 'body' => $body],
            'data' => $stringData,
            'android' => [
                'priority' => 'high',
                'notification' => ['sound' => 'default', 'channel_id' => 'sakk_push'],
            ],
            'apns' => [
                'payload' => ['aps' => ['sound' => 'default', 'badge' => 1]],
            ],
        ]);
    }

    /**
     * POST one message envelope. $ref is a log-safe identifier (token prefix/topic).
     */
    private function postMessage(array $message, string $ref, ?string $accessToken = null): bool
    {
        $accessToken ??= $this->getAccessToken();
        if ($accessToken === null) {
            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post(sprintf(self::V1_URL, $this->projectId), ['message' => $message]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCMService: send failed', [
                'ref' => substr($ref, 0, 24),
                'status' => $response->status(),
                'error' => $response->json('error.status') ?? $response->json('error.message'),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCMService: exception', [
                'message' => $e->getMessage(),
                'ref' => substr($ref, 0, 24),
            ]);
            return false;
        }
    }

    /**
     * Mint (and cache ~55m) an OAuth2 access token from the service account.
     */
    private function getAccessToken(): ?string
    {
        $email = $this->serviceAccount['client_email'] ?? null;
        if ($email === null) {
            return null;
        }

        $cacheKey = 'fcm:v1:access_token:' . md5($email);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $tokenUri = $this->serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $now = time();
        $jwt = $this->signJwt([
            'iss' => $email,
            'scope' => self::SCOPE,
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ]);
        if ($jwt === null) {
            return null;
        }

        try {
            $response = Http::asForm()->timeout(10)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            $accessToken = $response->json('access_token');
            if (!$response->successful() || !is_string($accessToken)) {
                Log::warning('FCMService: token exchange failed', [
                    'status' => $response->status(),
                    'error' => $response->json('error_description') ?? $response->json('error'),
                ]);
                return null;
            }

            $ttl = max(60, (int) ($response->json('expires_in') ?? 3600) - 300);
            Cache::put($cacheKey, $accessToken, $ttl);

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('FCMService: token exchange exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * RS256-sign a JWT claim set with the service-account private key (openssl).
     */
    private function signJwt(array $claims): ?string
    {
        $privateKey = $this->serviceAccount['private_key'] ?? null;
        if ($privateKey === null) {
            return null;
        }

        $segments = [
            $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64UrlEncode(json_encode($claims)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::error('FCMService: JWT signing failed (bad private key?)');
            return null;
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Resolve the service-account array from the integration credential or .env file.
     */
    private function resolveServiceAccount(?Integration $integration): ?array
    {
        $json = $integration?->getCredential('fcm_service_account');

        if (empty($json)) {
            $path = config('services.fcm.service_account_file');
            if ($path && is_file($path) && is_readable($path)) {
                $json = file_get_contents($path);
            }
        }

        // Convention fallback: a gitignored key file under storage/. Skipped
        // under tests so the "not configured" cases stay deterministic.
        if (empty($json) && !app()->runningUnitTests()) {
            $default = storage_path('app/firebase/service-account.json');
            if (is_file($default) && is_readable($default)) {
                $json = file_get_contents($default);
            }
        }

        if (empty($json)) {
            return null;
        }

        $decoded = is_array($json) ? $json : json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
