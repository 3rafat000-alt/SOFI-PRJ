<?php

use App\Models\Transaction;
use App\Models\User;
use App\Policies\TransactionPolicy;

beforeEach(function () {
    $this->policy = app(TransactionPolicy::class);
    $this->admin = User::factory()->make(['is_admin' => true]);
    $this->user = User::factory()->make(['is_admin' => false]);
    $this->transaction = Transaction::factory()->make();
});

it('allows admin to view any transactions', function () {
    expect($this->policy->viewAny($this->admin))->toBeTrue();
});

it('denies non-admin to view any transactions', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse();
});

it('allows admin to view a transaction', function () {
    expect($this->policy->view($this->admin, $this->transaction))->toBeTrue();
});

it('denies non-admin to view a transaction', function () {
    expect($this->policy->view($this->user, $this->transaction))->toBeFalse();
});

it('allows admin to reverse a transaction', function () {
    expect($this->policy->reverse($this->admin, $this->transaction))->toBeTrue();
});

it('denies non-admin to reverse a transaction', function () {
    expect($this->policy->reverse($this->user, $this->transaction))->toBeFalse();
});
