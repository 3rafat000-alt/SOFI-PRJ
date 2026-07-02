<?php

use App\Enums\KycStatus;
use App\Models\SavingsGoal;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function savingsUser(float $usd = 500): User
{
    $user = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'pin_code' => bcrypt('123456'),
    ]);
    $user->wallets()->where('currency', 'USD')->update([
        'balance' => $usd,
        'available_balance' => $usd,
    ]);
    return $user;
}

it('creates a savings goal', function () {
    Sanctum::actingAs(savingsUser());

    $res = $this->postJson('/api/v1/savings', [
        'name' => 'سيارة جديدة',
        'target_amount' => 5000,
    ]);

    $res->assertStatus(201);
    expect($res->json('data.name'))->toBe('سيارة جديدة');
    expect((float) $res->json('data.saved_amount'))->toBe(0.0);
    expect((float) $res->json('data.target_amount'))->toBe(5000.0);
});

it('creates a goal with an opening deposit', function () {
    Sanctum::actingAs(savingsUser(usd: 1000));

    $res = $this->postJson('/api/v1/savings', [
        'name' => 'الطوارئ',
        'target_amount' => 2000,
        'initial_amount' => 300,
    ]);

    $res->assertStatus(201);
    expect((float) $res->json('data.saved_amount'))->toBe(300.0);
});

it('deposits into a savings goal and debits the USD wallet', function () {
    $user = savingsUser(usd: 1000);
    Sanctum::actingAs($user);
    $goal = SavingsGoal::create(['user_id' => $user->id, 'name' => 'هدف', 'saved_amount' => 0, 'currency' => 'USD', 'status' => 'active']);

    $res = $this->postJson("/api/v1/savings/{$goal->id}/deposit", [
        'amount' => 250,
        'pin' => '123456',
    ]);

    $res->assertStatus(200);
    expect((float) $res->json('data.saved_amount'))->toBe(250.0);
    expect((float) $user->wallets()->where('currency', 'USD')->first()->available_balance)->toBe(750.0);
});

it('rejects deposit with insufficient balance', function () {
    $user = savingsUser(usd: 100);
    Sanctum::actingAs($user);
    $goal = SavingsGoal::create(['user_id' => $user->id, 'name' => 'هدف', 'saved_amount' => 0, 'currency' => 'USD', 'status' => 'active']);

    $this->postJson("/api/v1/savings/{$goal->id}/deposit", [
        'amount' => 500,
        'pin' => '123456',
    ])->assertStatus(422);
});

it('withdraws from a savings goal back to the wallet', function () {
    $user = savingsUser(usd: 1000);
    Sanctum::actingAs($user);
    $goal = SavingsGoal::create(['user_id' => $user->id, 'name' => 'هدف', 'saved_amount' => 400, 'currency' => 'USD', 'status' => 'active']);

    $res = $this->postJson("/api/v1/savings/{$goal->id}/withdraw", [
        'amount' => 150,
        'pin' => '123456',
    ]);

    $res->assertStatus(200);
    expect((float) $res->json('data.saved_amount'))->toBe(250.0);
});

it('marks a goal completed when the target is reached', function () {
    $user = savingsUser(usd: 1000);
    Sanctum::actingAs($user);
    $goal = SavingsGoal::create(['user_id' => $user->id, 'name' => 'هدف', 'target_amount' => 200, 'saved_amount' => 0, 'currency' => 'USD', 'status' => 'active']);

    $res = $this->postJson("/api/v1/savings/{$goal->id}/deposit", [
        'amount' => 200,
        'pin' => '123456',
    ]);

    $res->assertStatus(200);
    expect($res->json('data.status'))->toBe('completed');
});

it('blocks access to another user goal', function () {
    $owner = savingsUser();
    $goal = SavingsGoal::create(['user_id' => $owner->id, 'name' => 'هدف', 'saved_amount' => 0, 'currency' => 'USD', 'status' => 'active']);

    Sanctum::actingAs(savingsUser());
    $this->getJson("/api/v1/savings/{$goal->id}")->assertStatus(403);
});

it('returns savings summary', function () {
    $user = savingsUser(usd: 1000);
    Sanctum::actingAs($user);
    SavingsGoal::create(['user_id' => $user->id, 'name' => 'A', 'saved_amount' => 100, 'currency' => 'USD', 'status' => 'active']);
    SavingsGoal::create(['user_id' => $user->id, 'name' => 'B', 'saved_amount' => 250, 'currency' => 'USD', 'status' => 'active']);

    $res = $this->getJson('/api/v1/savings/summary');
    $res->assertStatus(200);
    expect((float) $res->json('data.total_saved'))->toBe(350.0);
    expect($res->json('data.goals_count'))->toBe(2);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/savings')->assertStatus(401);
    $this->postJson('/api/v1/savings')->assertStatus(401);
});
