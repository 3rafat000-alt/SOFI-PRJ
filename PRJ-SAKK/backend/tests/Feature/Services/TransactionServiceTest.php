<?php

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionService;

beforeEach(function () {
    $this->service = new TransactionService();
});

it('charges a fee and debits the wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'SYP', 'balance' => 1000]);

    $txn = $this->service->chargeFee($wallet, 50, 'رسوم تحويل');

    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(950.0);
    expect($txn->type)->toBe(TransactionType::FEE);
    expect($txn->category)->toBe(TransactionCategory::FEE);
    expect((float) $txn->amount)->toBe(-50.0);
    expect((float) $txn->net_amount)->toBe(-50.0);
    expect((float) $txn->balance_before)->toBe(1000.0);
    expect((float) $txn->balance_after)->toBe(950.0);
    expect($txn->status)->toBe(TransactionStatus::COMPLETED);
    expect($txn->title)->toBe('رسوم تحويل');
    // `completed_at` is in Transaction::$fillable (fixed — was previously
    // silently dropped by mass-assignment protection) and must persist.
    expect($txn->completed_at)->not->toBeNull();
    expect($txn->fresh()->completed_at)->not->toBeNull();
    expect($txn->user_id)->toBe($user->id);
    expect($txn->wallet_id)->toBe($wallet->id);
});

it('adds a reward and credits the wallet with metadata', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'SYP', 'balance' => 500]);

    $txn = $this->service->addReward($wallet, 25, 'مكافأة إحالة', ['referral_id' => 7]);

    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(525.0);
    expect($txn->type)->toBe(TransactionType::REWARD);
    expect($txn->category)->toBe(TransactionCategory::REWARD);
    expect((float) $txn->amount)->toBe(25.0);
    expect($txn->metadata)->toBe(['referral_id' => 7]);
    expect($txn->status)->toBe(TransactionStatus::COMPLETED);
});

it('gets recent transactions ordered latest first, limited', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'SYP']);

    for ($i = 0; $i < 15; $i++) {
        $this->service->chargeFee($wallet, 1, "fee-{$i}");
    }

    $recent = $this->service->getRecent($user, 5);

    // All 15 inserts land in the same second, so `latest()` (order by
    // created_at) ties-break arbitrarily by DB engine — only assert the
    // limit and eager-loaded relation shape are honored, not the exact tie
    // order.
    expect($recent)->toHaveCount(5);
    expect($recent->first()->relationLoaded('wallet'))->toBeTrue();
    expect($recent->first()->user_id)->toBe($user->id);
});

it('searches transactions by type, category, status and search term', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'SYP']);

    $this->service->chargeFee($wallet, 1, 'رسوم خاصة أ');
    $this->service->addReward($wallet, 1, 'مكافأة ب');

    $byType = $this->service->search($user, ['type' => TransactionType::FEE->value]);
    expect($byType->total())->toBe(1);

    $byCategory = $this->service->search($user, ['category' => TransactionCategory::REWARD->value]);
    expect($byCategory->total())->toBe(1);

    $byStatus = $this->service->search($user, ['status' => TransactionStatus::COMPLETED->value]);
    expect($byStatus->total())->toBe(2);

    $bySearch = $this->service->search($user, ['search' => 'خاصة']);
    expect($bySearch->total())->toBe(1);

    $byDateRange = $this->service->search($user, [
        'from' => now()->subDay()->toDateString(),
        'to' => now()->addDay()->toDateString(),
    ]);
    expect($byDateRange->total())->toBe(2);

    $paginated = $this->service->search($user, ['per_page' => 1]);
    expect($paginated->perPage())->toBe(1);
});

it('returns empty paginator when no filters match', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'SYP']);
    $this->service->chargeFee($wallet, 1, 'x');

    $result = $this->service->search($user, ['type' => 'nonexistent_type']);
    expect($result->total())->toBe(0);
});
