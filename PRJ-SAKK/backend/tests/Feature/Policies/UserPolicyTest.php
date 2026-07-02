<?php

use App\Models\User;
use App\Policies\UserPolicy;

beforeEach(function () {
    $this->policy = app(UserPolicy::class);
    $this->admin = User::factory()->make(['is_admin' => true]);
    $this->user = User::factory()->make(['is_admin' => false]);
    $this->otherUser = User::factory()->make(['is_admin' => false]);
});

it('allows admin to view any users', function () {
    expect($this->policy->viewAny($this->admin))->toBeTrue();
});

it('denies non-admin to view any users', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse();
});

it('allows admin to view a user', function () {
    expect($this->policy->view($this->admin, $this->otherUser))->toBeTrue();
});

it('denies non-admin to view a user', function () {
    expect($this->policy->view($this->user, $this->otherUser))->toBeFalse();
});

it('allows admin to update any user', function () {
    expect($this->policy->update($this->admin, $this->otherUser))->toBeTrue();
});

it('denies non-admin to update a user', function () {
    expect($this->policy->update($this->user, $this->otherUser))->toBeFalse();
});

it('allows admin to delete non-admin user', function () {
    expect($this->policy->delete($this->admin, $this->otherUser))->toBeTrue();
});

it('denies admin to delete another admin', function () {
    $otherAdmin = User::factory()->create(['is_admin' => true]);
    expect($this->policy->delete($this->admin, $otherAdmin))->toBeFalse();
});
