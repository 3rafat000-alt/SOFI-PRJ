<?php

use App\Services\CCPaymentService;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;

// These tests touch the `integrations` table, so they need a migrated DB.
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('CCPaymentService', function () {
    it('can be instantiated', function () {
        $service = new CCPaymentService();
        expect($service)->toBeInstanceOf(CCPaymentService::class);
    });

    it('is not active when integration is missing', function () {
        // Temporarily delete the integration and clear env fallback
        Integration::where('key', 'ccpayment')->delete();
        config(['services.ccpayment.app_id' => '']);
        config(['services.ccpayment.app_secret' => '']);
        
        $service = new CCPaymentService();
        expect($service->isActive())->toBeFalse();
        
        // Restore the integration
        $this->artisan('db:seed', ['--class' => 'CCPaymentSeeder', '--force' => true]);
    });

    it('throws exception when making request while inactive', function () {
        // Force the inactive state deterministically (the seeder activates it).
        Integration::where('key', 'ccpayment')->delete();
        config(['services.ccpayment.app_id' => '', 'services.ccpayment.app_secret' => '']);

        $service = new CCPaymentService();
        expect($service->isActive())->toBeFalse();
        expect(fn() => $service->getAssetList())
            ->toThrow(\RuntimeException::class, 'CCPayment غير مُكوّن أو غير نشط');
    });

    // SEV-4 regression: admin-toggled-off row must fail CLOSED, never fall
    // through to a stray env secret. This is the poison case — row PRESENT
    // and is_active=false — distinct from the row-ABSENT case above (which
    // legitimately falls back to env).
    it('fails closed when the integration row is present but inactive, even with env creds set', function () {
        Integration::updateOrCreate(
            ['key' => 'ccpayment'],
            ['name' => 'CCPayment', 'name_ar' => 'سي سي بايمنت', 'is_active' => false, 'credentials' => ['app_id' => 'db_id', 'app_secret' => 'db_secret'], 'settings' => []]
        );
        // A stray, fully-usable env secret must NOT resurrect the gateway.
        config(['services.ccpayment.app_id' => 'poison_env_id', 'services.ccpayment.app_secret' => 'poison_env_secret']);

        $service = new CCPaymentService();

        expect($service->isActive())->toBeFalse();
        expect(fn() => $service->getAssetList())
            ->toThrow(\RuntimeException::class, 'CCPayment غير مُكوّن أو غير نشط');

        // Restore the integration for subsequent tests.
        $this->artisan('db:seed', ['--class' => 'CCPaymentSeeder', '--force' => true]);
    });

    it('can generate HMAC-SHA256 signature', function () {
        // Create a mock service with test credentials
        $service = new class('test_app_id', 'test_app_secret') extends CCPaymentService {
            protected string $appId;
            protected string $appSecret;
            
            public function __construct(string $appId, string $appSecret) {
                $this->appId = $appId;
                $this->appSecret = $appSecret;
            }
            
            public function isActive(): bool {
                return true;
            }
        };
        
        // Use reflection to test the inherited private method
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('generateSign');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, '{}');
        
        expect($result)->toHaveKeys(['sign', 'timestamp']);
        expect($result['sign'])->toBeString();
        expect($result['timestamp'])->toBeString();
        expect(strlen($result['sign']))->toBe(64); // SHA256 hex length
    });

    it('resolves a chain-independent coin ID per symbol', function () {
        $service = new CCPaymentService();

        // CCPayment v2 has ONE coinId per coin (USDT = 1280); the network is a
        // separate `chain` param. The id must NOT vary by chain — varying it with
        // CoinMarketCap ids (ERC20 => 1, BEP20 => 1027) caused 13000 "unsupported
        // coin" on every non-TRC20 USDT withdrawal.
        expect($service->getCoinId('USDT', 'TRC20'))->toBe(1280);
        expect($service->getCoinId('USDT', 'ERC20'))->toBe(1280);
        expect($service->getCoinId('USDT', 'BEP20'))->toBe(1280);
        expect($service->getCoinId('usdt'))->toBe(1280); // case-insensitive, chain optional
        // Default fallback to the only enabled coin
        expect($service->getCoinId('UNKNOWN'))->toBe(1280);
    });

    it('maps app network codes to CCPayment chain symbols', function () {
        $service = new CCPaymentService();

        expect($service->ccChain('TRC20'))->toBe('TRX');
        expect($service->ccChain('ERC20'))->toBe('ETH');
        expect($service->ccChain('BEP20'))->toBe('BSC');
        expect($service->ccChain('BTC'))->toBe('BTC');
    });
});

describe('CCPaymentService HTTP Requests', function () {
    it('throws when inactive', function () {
        // Force the inactive state deterministically (the seeder activates it).
        Integration::where('key', 'ccpayment')->delete();
        config(['services.ccpayment.app_id' => '', 'services.ccpayment.app_secret' => '']);

        $service = new CCPaymentService();
        expect($service->isActive())->toBeFalse();
        expect(fn() => $service->getAssetList())
            ->toThrow(\RuntimeException::class, 'CCPayment غير مُكوّن أو غير نشط');
    });

    it('sends the exact signed body for an empty-param call (encodes as {} not [])', function () {
        // Use the env-fallback creds so the signature is deterministic.
        Integration::where('key', 'ccpayment')->delete();
        config(['services.ccpayment.app_id' => 'test_app', 'services.ccpayment.app_secret' => 'test_secret']);

        Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'success', 'data' => []], 200)]);

        (new CCPaymentService())->getAssetList(); // POST /getAppCoinAssetList, no params

        Http::assertSent(function ($request) {
            $body = $request->body();
            // The wire body must be the exact bytes that were signed — "{}", never "[]".
            if ($body !== '{}') {
                return false;
            }
            $timestamp = $request->header('Timestamp')[0];
            $expectedSign = hash_hmac('sha256', 'test_app' . $timestamp . $body, 'test_secret');

            return $request->header('Appid')[0] === 'test_app'
                && $request->header('Sign')[0] === $expectedSign;
        });
    });
});
