<?php

use App\Models\User;
use App\Traits\HasAccountLockout;

beforeEach(function () {
    $this->obj = new class {
        use HasAccountLockout;

        public function callCheckLockout(User $u): int
        {
            return $this->checkLockout($u);
        }

        public function callIncrement(User $u): void
        {
            $this->incrementLoginAttempts($u);
        }

        public function callReset(User $u): void
        {
            $this->resetLoginAttempts($u);
        }

        public function callGetUserByCredentials(array $c): ?User
        {
            return $this->getUserByCredentials($c);
        }

        public function callLockedOutResponse(\Illuminate\Http\Request $r, int $m)
        {
            return $this->lockedOutResponse($r, $m);
        }
    };
});

it('returns 0 lockout minutes when user is not locked', function () {
    $user = User::factory()->create(['locked_until' => null]);

    expect($this->obj->callCheckLockout($user))->toBe(0);
});

it('returns remaining minutes when user is currently locked', function () {
    $user = User::factory()->create(['locked_until' => now()->addMinutes(10)]);

    $remaining = $this->obj->callCheckLockout($user);

    expect($remaining)->toBeGreaterThanOrEqual(9);
    expect($remaining)->toBeLessThanOrEqual(11);
});

it('resets lockout fields when the lockout window has expired', function () {
    $user = User::factory()->create([
        'locked_until' => now()->subMinute(),
        'login_attempts' => 5,
        'last_failed_login_at' => now()->subHour(),
    ]);

    $remaining = $this->obj->callCheckLockout($user);

    expect($remaining)->toBe(0);
    $user->refresh();
    expect($user->login_attempts)->toBe(0);
    expect($user->locked_until)->toBeNull();
    expect($user->last_failed_login_at)->toBeNull();
});

it('increments login attempts without locking below the threshold', function () {
    $user = User::factory()->create(['login_attempts' => 2, 'locked_until' => null]);

    $this->obj->callIncrement($user);

    $user->refresh();
    expect($user->login_attempts)->toBe(3);
    expect($user->locked_until)->toBeNull();
    expect($user->last_failed_login_at)->not->toBeNull();
});

it('locks the account once max attempts is reached', function () {
    $user = User::factory()->create(['login_attempts' => 4, 'locked_until' => null]);

    $this->obj->callIncrement($user);

    $user->refresh();
    expect($user->login_attempts)->toBe(5);
    expect($user->locked_until)->not->toBeNull();
    expect(now()->lessThan($user->locked_until))->toBeTrue();
});

it('resets login attempts on successful login', function () {
    $user = User::factory()->create([
        'login_attempts' => 3,
        'locked_until' => now()->addMinutes(5),
        'last_failed_login_at' => now(),
    ]);

    $this->obj->callReset($user);

    $user->refresh();
    expect($user->login_attempts)->toBe(0);
    expect($user->locked_until)->toBeNull();
    expect($user->last_failed_login_at)->toBeNull();
});

it('does nothing on reset when attempts already clean', function () {
    $user = User::factory()->create([
        'login_attempts' => 0,
        'locked_until' => null,
        'last_failed_login_at' => null,
    ]);
    $updatedAtBefore = $user->updated_at;

    $this->obj->callReset($user);

    $user->refresh();
    expect($user->login_attempts)->toBe(0);
});

it('finds a user by email credentials', function () {
    $user = User::factory()->create(['email' => 'lockout-test@example.com']);

    $found = $this->obj->callGetUserByCredentials(['email' => 'lockout-test@example.com', 'password' => 'x']);

    expect($found->id)->toBe($user->id);
});

it('returns null when no user matches credentials', function () {
    $found = $this->obj->callGetUserByCredentials(['email' => 'nope@example.com', 'password' => 'x']);

    expect($found)->toBeNull();
});

it('builds a locked-out redirect response with errors', function () {
    $request = \Illuminate\Http\Request::create('/login', 'POST', ['email' => 'a@b.com']);
    $request->setLaravelSession(app('session.store'));

    $response = $this->obj->callLockedOutResponse($request, 12);

    expect($response->getStatusCode())->toBe(302);
    $errors = $response->getSession()->get('errors');
    expect($errors->get('email')[0])->toContain('12 دقيقة');
});
