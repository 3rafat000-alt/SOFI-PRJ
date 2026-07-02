<?php

use App\Models\KycVerification;
use App\Models\User;
use App\Policies\KycPolicy;

beforeEach(function () {
    $this->policy = app(KycPolicy::class);
    $this->admin = User::factory()->make(['is_admin' => true]);
    $this->user = User::factory()->make(['is_admin' => false]);
    $this->kyc = KycVerification::factory()->make(['status' => 'pending']);
});

it('allows admin to view any KYC', function () {
    expect($this->policy->viewAny($this->admin))->toBeTrue();
});

it('denies non-admin to view any KYC', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse();
});

it('allows admin to view a KYC record', function () {
    expect($this->policy->view($this->admin, $this->kyc))->toBeTrue();
});

it('denies non-admin to view a KYC record', function () {
    expect($this->policy->view($this->user, $this->kyc))->toBeFalse();
});

it('allows admin to create KYC', function () {
    expect($this->policy->create($this->admin))->toBeTrue();
});

it('denies non-admin to create KYC', function () {
    expect($this->policy->create($this->user))->toBeFalse();
});

it('allows admin to update KYC', function () {
    expect($this->policy->update($this->admin, $this->kyc))->toBeTrue();
});

it('denies non-admin to update KYC', function () {
    expect($this->policy->update($this->user, $this->kyc))->toBeFalse();
});

it('allows admin to delete KYC', function () {
    expect($this->policy->delete($this->admin, $this->kyc))->toBeTrue();
});

it('denies non-admin to delete KYC', function () {
    expect($this->policy->delete($this->user, $this->kyc))->toBeFalse();
});

it('allows admin to approve pending KYC', function () {
    expect($this->policy->approve($this->admin, $this->kyc))->toBeTrue();
});

it('denies admin approving non-pending KYC', function () {
    $kyc = KycVerification::factory()->make(['status' => 'approved']);
    expect($this->policy->approve($this->admin, $kyc))->toBeFalse();
});

it('allows admin to reject pending KYC', function () {
    expect($this->policy->reject($this->admin, $this->kyc))->toBeTrue();
});

it('denies non-admin to reject KYC', function () {
    expect($this->policy->reject($this->user, $this->kyc))->toBeFalse();
});
