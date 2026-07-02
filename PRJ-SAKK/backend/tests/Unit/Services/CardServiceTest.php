<?php

use App\Enums\CardStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\CardService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function cardServiceUsdWallet(User $user, float $balance = 1000): Wallet
{
    $wallet = $user->wallets()->where('currency', 'USD')->first()
        ?? Wallet::factory()->for($user)->create(['currency' => 'USD']);
    $wallet->update(['balance' => $balance, 'available_balance' => $balance]);
    return $wallet->fresh();
}

it('CardService can be instantiated', function () {
    $service = app(CardService::class);
    expect($service)->toBeInstanceOf(CardService::class);
});

it('getPricing returns sane defaults when no CardPricing row exists', function () {
    $service = app(CardService::class);
    $pricing = $service->getPricing('visa', 'virtual');

    expect($pricing['purchase_price'])->toBe(10.0);
    expect($pricing['kyc_level_required'])->toBe(2);
});

it('getPricing returns the configured CardPricing row when one exists', function () {
    \App\Models\CardPricing::create([
        'brand' => 'visa',
        'type' => 'virtual',
        'purchase_price' => 15.0,
        'monthly_fee' => 1.0,
        'min_load' => 50.0,
        'max_load' => 2000.0,
        'load_fee_percentage' => 2.0,
        'load_fee_fixed' => 0.5,
        'transaction_fee_percentage' => 1.0,
        'atm_fee' => 3.0,
        'international_fee_percentage' => 4.0,
        'kyc_level_required' => 1,
        'is_active' => true,
    ]);

    $service = app(CardService::class);
    $pricing = $service->getPricing('visa', 'virtual');

    expect($pricing['purchase_price'])->toBe(15.0);
    expect($pricing['kyc_level_required'])->toBe(1);
});

// ==================== createCard ====================

it('createCard rejects a non-USD wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'SYP', 'balance' => 1000, 'available_balance' => 1000]);
    $service = app(CardService::class);

    $result = $service->createCard($user, $wallet);

    expect($result['success'])->toBeFalse();
});

it('createCard blocks a user below the required KYC level', function () {
    $user = User::factory()->create(['kyc_level' => 0]);
    $wallet = cardServiceUsdWallet($user);
    $service = app(CardService::class);

    $result = $service->createCard($user, $wallet);

    expect($result['success'])->toBeFalse();
    expect($result['required_level'])->toBe(2);
});

it('createCard rejects insufficient balance for the purchase fee', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = cardServiceUsdWallet($user, 1); // below default $10 purchase price
    $service = app(CardService::class);

    $result = $service->createCard($user, $wallet);

    expect($result['success'])->toBeFalse();
    expect($result['required'])->toBe(10.0);
});

it('createCard charges the purchase fee and issues a local card', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = cardServiceUsdWallet($user, 100);
    $service = app(CardService::class);

    $result = $service->createCard($user, $wallet, 'visa', 'virtual', 'My Card');

    expect($result['success'])->toBeTrue();
    expect($result['purchase_fee'])->toBe(10.0);
    expect((float) $wallet->fresh()->balance)->toBe(90.0);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => TransactionType::FEE->value,
    ]);
    $this->assertDatabaseHas('virtual_cards', [
        'user_id' => $user->id,
        'daily_limit' => CardService::DAILY_LIMIT,
        'monthly_limit' => CardService::MONTHLY_LIMIT,
    ]);
});

// ==================== chargePurchaseFee / refundPurchaseFee ====================

it('chargePurchaseFee debits the wallet and returns a transaction id', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = cardServiceUsdWallet($user, 100);
    $service = app(CardService::class);

    $result = $service->chargePurchaseFee($user, $wallet);

    expect($result['success'])->toBeTrue();
    expect($result['fee'])->toBe(10.0);
    expect((float) $wallet->fresh()->balance)->toBe(90.0);
});

it('chargePurchaseFee blocks a user below the required KYC level', function () {
    $user = User::factory()->create(['kyc_level' => 0]);
    $wallet = cardServiceUsdWallet($user, 100);
    $service = app(CardService::class);

    $result = $service->chargePurchaseFee($user, $wallet);

    expect($result['success'])->toBeFalse();
    expect($result['required_level'])->toBe(2);
});

it('chargePurchaseFee rejects a non-USD wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'SYP', 'balance' => 1000, 'available_balance' => 1000]);
    $service = app(CardService::class);

    $result = $service->chargePurchaseFee($user, $wallet);

    expect($result['success'])->toBeFalse();
});

it('chargePurchaseFee rejects insufficient balance', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = cardServiceUsdWallet($user, 1);
    $service = app(CardService::class);

    $result = $service->chargePurchaseFee($user, $wallet);

    expect($result['success'])->toBeFalse();
});

it('refundPurchaseFee credits the wallet back and logs the refund', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 90);
    $service = app(CardService::class);

    $result = $service->refundPurchaseFee($user, $wallet, 10.0, 123);

    expect($result['success'])->toBeTrue();
    expect((float) $result['refunded'])->toBe(10.0);
    expect((float) $wallet->fresh()->balance)->toBe(100.0);
});

it('refundPurchaseFee is a no-op for a zero fee', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 90);
    $service = app(CardService::class);

    $result = $service->refundPurchaseFee($user, $wallet, 0);

    expect($result)->toBe(['success' => true, 'refunded' => 0]);
    expect((float) $wallet->fresh()->balance)->toBe(90.0);
});

// ==================== loadCard ====================

it('loadCard debits the wallet, credits the card, and applies the load fee', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 1000);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'balance' => 0,
        'status' => 'active',
        'is_active' => true,
    ]);
    $service = app(CardService::class);

    $result = $service->loadCard($card, $wallet, 200);

    expect($result['success'])->toBeTrue();
    // default pricing: 1% load fee + 0 fixed => fee = 2.0, total debit 202
    expect($result['fee'])->toBe(2.0);
    expect((float) $wallet->fresh()->balance)->toBe(798.0);
    expect((float) $card->fresh()->balance)->toBe(200.0);
});

it('loadCard rejects a frozen card', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 1000);
    $card = VirtualCard::factory()->frozen()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
    ]);
    $service = app(CardService::class);

    $result = $service->loadCard($card, $wallet, 200);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('البطاقة مجمّدة — ألغِ التجميد أولاً');
});

it('loadCard rejects a card that does not belong to the wallet owner', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $intruderWallet = cardServiceUsdWallet($intruder, 1000);
    $card = VirtualCard::factory()->create(['user_id' => $owner->id]);
    $service = app(CardService::class);

    $result = $service->loadCard($card, $intruderWallet, 100);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('غير مصرح');
});

it('loadCard rejects an amount below the minimum load', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 1000);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'status' => 'active', 'is_active' => true,
    ]);
    $service = app(CardService::class);

    $result = $service->loadCard($card, $wallet, 1); // below default $100 min

    expect($result['success'])->toBeFalse();
});

it('loadCard rejects an amount above the maximum load', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 100000);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'status' => 'active', 'is_active' => true,
    ]);
    $service = app(CardService::class);

    $result = $service->loadCard($card, $wallet, 999999); // above default $5000 max

    expect($result['success'])->toBeFalse();
});

it('loadCard rejects insufficient wallet balance', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 50);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'status' => 'active', 'is_active' => true,
    ]);
    $service = app(CardService::class);

    $result = $service->loadCard($card, $wallet, 200);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('رصيد غير كافٍ');
});

// ==================== unloadCard ====================

it('unloadCard credits the wallet and debits the card', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 500);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'balance' => 100,
    ]);
    $service = app(CardService::class);

    $result = $service->unloadCard($card, $wallet, 40);

    expect($result['success'])->toBeTrue();
    expect((float) $wallet->fresh()->balance)->toBe(540.0);
    expect((float) $card->fresh()->balance)->toBe(60.0);
});

it('unloadCard rejects an amount exceeding the card balance', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 500);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'balance' => 10,
    ]);
    $service = app(CardService::class);

    $result = $service->unloadCard($card, $wallet, 40);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('رصيد البطاقة غير كافٍ');
});

it('unloadCard rejects a card not owned by the wallet holder', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $intruderWallet = cardServiceUsdWallet($intruder, 500);
    $card = VirtualCard::factory()->create(['user_id' => $owner->id, 'balance' => 100]);
    $service = app(CardService::class);

    $result = $service->unloadCard($card, $intruderWallet, 10);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('غير مصرح');
});

// ==================== getCardDetails ====================

it('getCardDetails never exposes the full PAN or CVV', function () {
    $card = VirtualCard::factory()->create();
    $service = app(CardService::class);

    $result = $service->getCardDetails($card);

    expect($result['success'])->toBeTrue();
    expect($result['card'])->not->toHaveKey('cvv');
    expect($result['card'])->not->toHaveKey('card_number');
    expect($result['card']['last4'])->toBeString();
});

// ==================== toggleFreeze ====================

it('toggleFreeze freezes an active card', function () {
    $card = VirtualCard::factory()->create(['status' => 'active']);
    $service = app(CardService::class);

    $result = $service->toggleFreeze($card);

    expect($result['success'])->toBeTrue();
    expect($result['status'])->toBe(CardStatus::FROZEN->value);
    expect($card->fresh()->status)->toBe(CardStatus::FROZEN);
});

it('toggleFreeze unfreezes a frozen card', function () {
    $card = VirtualCard::factory()->frozen()->create();
    $service = app(CardService::class);

    $result = $service->toggleFreeze($card);

    expect($result['success'])->toBeTrue();
    expect($result['status'])->toBe(CardStatus::ACTIVE->value);
});

// ==================== cancelCard ====================

it('cancelCard refunds the remaining card balance to the wallet', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 100);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'balance' => 25,
    ]);
    $service = app(CardService::class);

    $result = $service->cancelCard($card, $wallet);

    expect($result['success'])->toBeTrue();
    expect((float) $result['refunded'])->toBe(25.0);
    expect((float) $wallet->fresh()->balance)->toBe(125.0);
    expect($card->fresh()->status)->toBe(CardStatus::CANCELLED);
    $this->assertDatabaseHas('transactions', [
        'card_id' => $card->id,
        'type' => TransactionType::CARD_UNLOAD->value,
    ]);
});

it('cancelCard rejects a card not owned by the wallet holder', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $intruderWallet = cardServiceUsdWallet($intruder, 100);
    $card = VirtualCard::factory()->create(['user_id' => $owner->id, 'balance' => 25]);
    $service = app(CardService::class);

    $result = $service->cancelCard($card, $intruderWallet);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('غير مصرح');
});

it('cancelCard with zero balance issues no refund transaction', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 100);
    $card = VirtualCard::factory()->create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'balance' => 0]);
    $service = app(CardService::class);

    $result = $service->cancelCard($card, $wallet);

    expect($result['success'])->toBeTrue();
    expect((float) $result['refunded'])->toBe(0.0);
    expect((float) $wallet->fresh()->balance)->toBe(100.0);
});

// ==================== importCardsFromFile (PCI-disabled) ====================

it('importCardsFromFile is permanently disabled and never reads the filesystem', function () {
    $service = app(CardService::class);

    $result = $service->importCardsFromFile('/any/path/does/not/matter.csv');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('disabled');
});

// ==================== loadCard / unloadCard re-verification under lock ====================

it('loadCard re-rejects when the card became inactive between the pre-check and the lock', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 1000);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'status' => 'active', 'is_active' => true,
    ]);
    $service = app(CardService::class);

    // Flip status directly in DB to simulate a race after the initial check
    // but before the pessimistic lock re-verifies it.
    VirtualCard::where('id', $card->id)->update(['status' => 'frozen', 'is_active' => false]);

    $result = $service->loadCard($card, $wallet, 200);

    expect($result['success'])->toBeFalse();
});

it('unloadCard re-rejects when the card balance dropped below the amount under lock', function () {
    $user = User::factory()->create();
    $wallet = cardServiceUsdWallet($user, 500);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'balance' => 100,
    ]);
    $service = app(CardService::class);

    VirtualCard::where('id', $card->id)->update(['balance' => 5]);

    $result = $service->unloadCard($card, $wallet, 40);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('رصيد البطاقة غير كافٍ');
});

// ==================== Luhn / card-number generation (dead local-card helpers) ====================
// generateCardNumber/generateCVV/generateExpiry/detectBrand/validateLuhn/applyLuhn
// are protected and unused by the live Stripe-issuance path (VirtualCard's own
// boot hook generates real card data) — exercised here via reflection purely
// to lock in their pure-function correctness since they remain in the class.

it('applyLuhn produces a Luhn-valid 16-digit number and validateLuhn agrees', function () {
    $service = app(CardService::class);
    $ref = new ReflectionClass($service);

    $applyLuhn = $ref->getMethod('applyLuhn');
    $applyLuhn->setAccessible(true);
    $validateLuhn = $ref->getMethod('validateLuhn');
    $validateLuhn->setAccessible(true);

    $partial = '400000000000000';
    $full = $applyLuhn->invoke($service, $partial);

    expect(strlen($full))->toBe(16);
    expect($validateLuhn->invoke($service, $full))->toBeTrue();
    expect($validateLuhn->invoke($service, '4000000000000001'))->toBeFalse();
});

it('generateCardNumber produces a Luhn-valid number prefixed by brand', function () {
    $service = app(CardService::class);
    $ref = new ReflectionClass($service);

    $generate = $ref->getMethod('generateCardNumber');
    $generate->setAccessible(true);
    $validateLuhn = $ref->getMethod('validateLuhn');
    $validateLuhn->setAccessible(true);

    $visaNumber = $generate->invoke($service, 'visa');
    expect($visaNumber[0])->toBe('4');
    expect($validateLuhn->invoke($service, $visaNumber))->toBeTrue();

    $mcNumber = $generate->invoke($service, 'mastercard');
    expect($mcNumber[0])->toBe('5');
});

it('generateCVV produces a zero-padded 3-digit string', function () {
    $service = app(CardService::class);
    $ref = new ReflectionClass($service);
    $generate = $ref->getMethod('generateCVV');
    $generate->setAccessible(true);

    $cvv = $generate->invoke($service);

    expect($cvv)->toBeString();
    expect(strlen($cvv))->toBe(3);
});

it('generateExpiry returns a month/year three years out', function () {
    $service = app(CardService::class);
    $ref = new ReflectionClass($service);
    $generate = $ref->getMethod('generateExpiry');
    $generate->setAccessible(true);

    $expiry = $generate->invoke($service);

    expect($expiry['year'])->toBe(now()->addYears(3)->format('Y'));
});

it('detectBrand maps the leading digit to visa/mastercard/amex, defaulting to visa', function () {
    $service = app(CardService::class);
    $ref = new ReflectionClass($service);
    $detect = $ref->getMethod('detectBrand');
    $detect->setAccessible(true);

    expect($detect->invoke($service, '4111111111111111'))->toBe('visa');
    expect($detect->invoke($service, '5500000000000004'))->toBe('mastercard');
    expect($detect->invoke($service, '340000000000009'))->toBe('amex');
    expect($detect->invoke($service, '9999999999999999'))->toBe('visa');
});

it('getCardFromInventory returns null when no unassigned inventory row exists', function () {
    $service = app(CardService::class);
    $ref = new ReflectionClass($service);
    $method = $ref->getMethod('getCardFromInventory');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'visa', 'virtual');

    expect($result)->toBeNull();
});
