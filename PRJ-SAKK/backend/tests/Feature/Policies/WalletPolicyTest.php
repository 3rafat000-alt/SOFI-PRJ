<?php

use App\Models\User;
use App\Models\Wallet;
use App\Policies\WalletPolicy;

beforeEach(function () {
    $this->policy = app(WalletPolicy::class);
    $this->admin = User::factory()->make(['is_admin' => true]);
    $this->user = User::factory()->make(['is_admin' => false]);
    $this->wallet = Wallet::factory()->make();
});

it('allows admin to view any wallets', function () {
    expect($this->policy->viewAny($this->admin))->toBeTrue();
});

it('denies non-admin to view any wallets', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse();
});

it('allows admin to view a wallet', function () {
    expect($this->policy->view($this->admin, $this->wallet))->toBeTrue();
});

it('denies non-admin to view a wallet', function () {
    expect($this->policy->view($this->user, $this->wallet))->toBeFalse();
});

it('allows admin to freeze a wallet', function () {
    expect($this->policy->freeze($this->admin, $this->wallet))->toBeTrue();
});

it('denies non-admin to freeze a wallet', function () {
    expect($this->policy->freeze($this->user, $this->wallet))->toBeFalse();
});

it('allows admin to unfreeze a wallet', function () {
    expect($this->policy->unfreeze($this->admin, $this->wallet))->toBeTrue();
});

it('denies non-admin to unfreeze a wallet', function () {
    expect($this->policy->unfreeze($this->user, $this->wallet))->toBeFalse();
});
