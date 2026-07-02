<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you return to your test case determines which
| test case class will be used for your tests. Pest's test
| case automatically extends PHPUnit's TestCase.
|
*/

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature');

uses(
    Tests\TestCase::class,
)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're finished building your library, you can extend Pest's
| expectation API to add custom expectations specific to your project.
|
*/

expect()->extend('toBePositive', function () {
    return $this->toBeGreaterThan(0);
});

expect()->extend('toBeMoney', function () {
    return $this->toBeNumeric();
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some
| testing code specific to your project that doesn't fit in
| the expectation or helper files. In that case, you can
| create your own helper functions for reusability.
|
*/

function createAuthenticatedUser(array $attributes = []): \App\Models\User
{
    $user = \App\Models\User::factory()->create($attributes);
    $token = $user->createToken('test-token')->plainTextToken;
    
    test()->withHeader('Authorization', "Bearer {$token}");
    
    return $user;
}
