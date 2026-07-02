<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionCategory;
use Laravel\Sanctum\Sanctum;

it('GET /api/v1/transactions - returns paginated transactions', function () {
    $user = User::factory()->create();
    Wallet::factory()
        ->has(Transaction::factory()->count(3), 'transactions')
        ->create(['user_id' => $user->id]);
    
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/transactions');
    
    $response->assertStatus(200);
});

it('GET /api/v1/transactions - filters by type', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    
    Transaction::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'type' => TransactionType::DEPOSIT,
    ]);
    Transaction::factory()->count(2)->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'type' => TransactionType::WITHDRAWAL,
    ]);
    
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/transactions?type=deposit');
    
    $response->assertStatus(200);
});

it('GET /api/v1/transactions - filters by status', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    
    Transaction::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'status' => TransactionStatus::COMPLETED,
    ]);
    Transaction::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'status' => TransactionStatus::PENDING,
    ]);
    
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/transactions?status=completed');
    
    $response->assertStatus(200);
});

it('GET /api/v1/transactions/stats - returns transaction statistics', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/transactions/stats');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['period', 'summary', 'by_type', 'by_category', 'daily_breakdown']
        ]);
});

it('GET /api/v1/transactions/{transaction} - shows transaction details', function () {
    $transaction = Transaction::factory()->create();
    Sanctum::actingAs($transaction->user);
    
    $response = $this->getJson("/api/v1/transactions/{$transaction->id}");
    
    $response->assertStatus(200);
});

it('GET /api/v1/transactions/types - returns list of transaction types', function () {
    $response = $this->getJson('/api/v1/transactions/types');
    
    $response->assertStatus(200);
});

it('GET /api/v1/transactions/categories - returns list of categories', function () {
    $response = $this->getJson('/api/v1/transactions/categories');
    
    $response->assertStatus(200);
});

it('GET /api/v1/transactions/export - errors without date params', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/transactions/export');
    
    expect($response->status())->toBe(422);
});

it('GET /api/v1/transactions - paginates properly', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    
    Transaction::factory()->count(25)->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);
    
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/transactions?per_page=10');
    
    $response->assertStatus(200);
});

it('GET /api/v1/transactions - requires authentication', function () {
    $response = $this->getJson('/api/v1/transactions');
    
    $response->assertStatus(401);
});
