<?php

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Customer-facing support tickets (App\Http\Controllers\API\SupportTicketController).
 * Mirrors the admin side; internal notes are never exposed here.
 */

function stCustomer(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

it('lists only the authenticated user\'s tickets, newest activity first', function () {
    Mail::fake();
    $user = stCustomer();
    $other = stCustomer();

    $mine = SupportTicket::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'ticket_number' => 'TK-000001',
        'subject' => 'My ticket',
        'description' => 'desc',
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'open',
    ]);
    SupportTicket::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $other->id,
        'ticket_number' => 'TK-000002',
        'subject' => 'Not mine',
        'description' => 'desc',
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/support/tickets')
        ->assertOk()
        ->assertJsonPath('success', true);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['ticket_number'])->toBe($mine->ticket_number);
});

it('opens a new ticket and stores the description as the first message', function () {
    Mail::fake();
    $user = stCustomer();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/support/tickets', [
        'subject' => 'App keeps crashing',
        'description' => 'Every time I open the wallet tab it crashes.',
        'category' => 'technical',
        'priority' => 'high',
    ])->assertStatus(201)->assertJsonPath('success', true);

    $ticket = SupportTicket::where('user_id', $user->id)->first();
    expect($ticket)->not->toBeNull();
    expect($ticket->subject)->toBe('App keeps crashing');
    expect($ticket->category)->toBe('technical');
    expect($ticket->priority)->toBe('high');
    expect($ticket->status)->toBe('open');
    expect(TicketMessage::where('ticket_id', $ticket->id)->where('is_internal', false)->count())->toBe(1);

    Mail::assertSent(\App\Mail\SupportTicketMail::class);
});

it('defaults category and priority when omitted on store', function () {
    Mail::fake();
    $user = stCustomer();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/support/tickets', [
        'subject' => 'General question',
        'description' => 'How do I change my password?',
    ])->assertStatus(201);

    $ticket = SupportTicket::where('user_id', $user->id)->first();
    expect($ticket->category)->toBe('general');
    expect($ticket->priority)->toBe('medium');
});

it('rejects a ticket store missing subject/description', function () {
    Mail::fake();
    Sanctum::actingAs(stCustomer());

    $this->postJson('/api/v1/support/tickets', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['subject', 'description']);
});

it('rejects an invalid category on store', function () {
    Mail::fake();
    Sanctum::actingAs(stCustomer());

    $this->postJson('/api/v1/support/tickets', [
        'subject' => 'x',
        'description' => 'y',
        'category' => 'not-a-real-category',
    ])->assertStatus(422)->assertJsonValidationErrors(['category']);
});

it('allows attaching a related transaction the user owns', function () {
    Mail::fake();
    $user = stCustomer();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $tx = Transaction::factory()->deposit()->create(['user_id' => $user->id, 'wallet_id' => $wallet->id]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/support/tickets', [
        'subject' => 'Deposit missing',
        'description' => 'My deposit never landed',
        'related_transaction' => $tx->id,
    ])->assertStatus(201);

    $ticket = SupportTicket::where('user_id', $user->id)->first();
    expect($ticket->related_transaction)->toBe($tx->id);
});

it('rejects a related_transaction that belongs to another user', function () {
    Mail::fake();
    $owner = stCustomer();
    $wallet = $owner->wallets()->where('currency', 'USD')->first();
    $tx = Transaction::factory()->deposit()->create(['user_id' => $owner->id, 'wallet_id' => $wallet->id]);

    $stranger = stCustomer();
    Sanctum::actingAs($stranger);

    $this->postJson('/api/v1/support/tickets', [
        'subject' => 'Deposit missing',
        'description' => 'Not my transaction',
        'related_transaction' => $tx->id,
    ])->assertStatus(422)->assertJsonValidationErrors(['related_transaction']);
});

it('shows one ticket with its public message thread, hiding internal notes', function () {
    Mail::fake();
    $user = stCustomer();
    $ticket = SupportTicket::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'ticket_number' => 'TK-000003',
        'subject' => 'Test',
        'description' => 'desc',
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'open',
    ]);
    TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'message' => 'public msg', 'is_internal' => false]);
    $agent = stCustomer();
    TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $agent->id, 'message' => 'internal note', 'is_internal' => true]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/support/tickets/{$ticket->uuid}")
        ->assertOk()
        ->assertJsonPath('success', true);

    $messages = collect($response->json('data.messages'))->pluck('message');
    expect($messages)->toContain('public msg');
    expect($messages)->not->toContain('internal note');
});

it('show returns 404 for a ticket owned by another user', function () {
    Mail::fake();
    $owner = stCustomer();
    $ticket = SupportTicket::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $owner->id,
        'ticket_number' => 'TK-000004',
        'subject' => 'Test',
        'description' => 'desc',
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    Sanctum::actingAs(stCustomer());

    $this->getJson("/api/v1/support/tickets/{$ticket->uuid}")->assertNotFound();
});

it('reply appends a message and re-opens a resolved ticket', function () {
    Mail::fake();
    $user = stCustomer();
    $ticket = SupportTicket::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'ticket_number' => 'TK-000005',
        'subject' => 'Test',
        'description' => 'desc',
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'resolved',
        'resolved_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/support/tickets/{$ticket->uuid}/reply", [
        'message' => 'Actually still broken',
    ])->assertOk()->assertJsonPath('success', true);

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe('open');
    expect($fresh->resolved_at)->toBeNull();
    expect(TicketMessage::where('ticket_id', $ticket->id)->where('message', 'Actually still broken')->exists())->toBeTrue();

    Mail::assertSent(\App\Mail\SupportTicketMail::class);
});

it('reply rejects an empty message', function () {
    Mail::fake();
    $user = stCustomer();
    $ticket = SupportTicket::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'ticket_number' => 'TK-000006',
        'subject' => 'Test',
        'description' => 'desc',
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'open',
    ]);
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/support/tickets/{$ticket->uuid}/reply", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

it('categories returns the static list with arabic labels', function () {
    Sanctum::actingAs(stCustomer());

    $response = $this->getJson('/api/v1/support/categories')
        ->assertOk()
        ->assertJsonPath('success', true);

    $values = collect($response->json('data'))->pluck('value');
    expect($values->all())->toBe(['general', 'transaction', 'card', 'kyc', 'technical', 'billing']);
});

it('never blocks the request when the support inbox mail send throws', function () {
    Mail::shouldReceive('to')->andThrow(new \Exception('smtp down'));
    $user = stCustomer();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/support/tickets', [
        'subject' => 'Still works',
        'description' => 'even if mail fails',
    ])->assertStatus(201)->assertJsonPath('success', true);

    expect(SupportTicket::where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects an unauthenticated request', function () {
    $this->getJson('/api/v1/support/tickets')->assertStatus(401);
});
