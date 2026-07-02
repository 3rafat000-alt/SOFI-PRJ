<?php

use App\Http\Resources\CardResource;
use App\Models\VirtualCard;
use App\Models\User;
use App\Models\Wallet;

it('formats card resource with masked details', function () {
    $card = VirtualCard::factory()->create();

    $resource = new CardResource($card);
    $array = $resource->toArray(request());

    expect($array)->toHaveKey('id');
    expect($array)->toHaveKey('uuid');
    expect($array)->toHaveKey('card_number_masked');
    expect($array)->toHaveKey('last_four');
    expect($array)->toHaveKey('expiry');
    expect($array)->toHaveKey('cardholder_name');
    expect($array)->toHaveKey('card_type');
    expect($array)->toHaveKey('brand');
    expect($array)->toHaveKey('status');
    expect($array)->toHaveKey('balance');
    expect($array)->toHaveKey('is_active');
});

it('exposes masked card number not full PAN', function () {
    $card = VirtualCard::factory()->create([
        'card_number_masked' => '************1234',
    ]);

    $resource = new CardResource($card);
    $array = $resource->toArray(request());

    expect($array['card_number_masked'])->toBe('************1234');
    expect($array['last_four'])->toBe('1234');
    // Ensure full PAN is not present
    expect($array)->not->toHaveKey('card_number');
});

it('includes frozen reason when status is frozen', function () {
    $card = VirtualCard::factory()->create([
        'status' => \App\Enums\CardStatus::FROZEN,
        'frozen_reason' => 'Lost card reported',
    ]);

    $resource = new CardResource($card);
    $array = $resource->toArray(request());

    expect($array['frozen_reason'])->toBe('Lost card reported');
});

it('includes wallet relation when loaded', function () {
    $wallet = Wallet::factory()->create();
    $card = VirtualCard::factory()->for($wallet)->create();
    $card->load('wallet');

    $resource = new CardResource($card);
    $array = $resource->toArray(request());

    expect($array['wallet'])->not->toBeNull();
    expect($array['wallet']['id'])->toBe($wallet->id);
});
