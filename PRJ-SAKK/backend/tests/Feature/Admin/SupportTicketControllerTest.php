<?php

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

function makeSupportTicket(array $overrides = []): SupportTicket
{
    return SupportTicket::create(array_merge([
        'uuid' => Str::uuid(),
        'user_id' => User::factory()->create()->id,
        'ticket_number' => 'TCK-' . Str::random(6),
        'subject' => 'مشكلة في التحويل',
        'description' => 'لم يصل التحويل بعد',
        'category' => 'transaction',
        'priority' => 'medium',
        'status' => 'open',
    ], $overrides));
}

it('lists tickets with kpi counters', function () {
    makeSupportTicket(['status' => 'open']);
    makeSupportTicket(['status' => 'in_progress']);
    makeSupportTicket(['status' => 'closed', 'priority' => 'urgent']);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index'));

    $response->assertOk()->assertViewIs('admin.support.index');
    expect($response->viewData('kpis')['total'])->toBe(3);
});

it('filters tickets by status, priority, category and search term', function () {
    $t1 = makeSupportTicket(['status' => 'open', 'priority' => 'urgent', 'category' => 'kyc', 'subject' => 'مشكلة توثيق']);
    makeSupportTicket(['status' => 'closed', 'priority' => 'low', 'category' => 'general']);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index', ['status' => 'open']));
    expect($response->viewData('tickets')->total())->toBe(1);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index', ['priority' => 'urgent']));
    expect($response->viewData('tickets')->total())->toBe(1);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index', ['category' => 'kyc']));
    expect($response->viewData('tickets')->total())->toBe(1);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index', ['q' => $t1->ticket_number]));
    expect($response->viewData('tickets')->total())->toBe(1);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index', ['q' => 'توثيق']));
    expect($response->viewData('tickets')->total())->toBe(1);
});

it('searches tickets by the customer name or email', function () {
    $user = User::factory()->create(['first_name' => 'خالد', 'last_name' => 'الحسن', 'email' => 'khaled@example.com']);
    makeSupportTicket(['user_id' => $user->id]);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index', ['q' => 'خالد']));
    expect($response->viewData('tickets')->total())->toBe(1);

    $response = $this->actingAs($this->admin)->get(route('admin.support.index', ['q' => 'khaled@example.com']));
    expect($response->viewData('tickets')->total())->toBe(1);
});

it('shows a ticket thread with user, assignee and messages loaded', function () {
    $ticket = makeSupportTicket();
    TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $this->admin->id, 'message' => 'مرحباً']);

    $response = $this->actingAs($this->admin)->get(route('admin.support.show', $ticket));

    $response->assertOk()->assertViewIs('admin.support.show');
    expect($response->viewData('ticket')->relationLoaded('messages'))->toBeTrue();
    expect($response->viewData('statuses'))->toContain('open', 'resolved', 'closed');
});

it('replying publicly moves an open ticket to in_progress and notifies the customer by mail', function () {
    Mail::fake();
    $ticket = makeSupportTicket(['status' => 'open']);

    $this->actingAs($this->admin)
        ->post(route('admin.support.reply', $ticket), ['message' => 'شكراً لتواصلك، نعمل على حل المشكلة.'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->status)->toBe('in_progress');
    $this->assertDatabaseHas('ticket_messages', [
        'ticket_id' => $ticket->id,
        'is_internal' => false,
    ]);
    Mail::assertSent(\App\Mail\SupportTicketMail::class);
});

it('does not advance a non-open ticket status on public reply', function () {
    Mail::fake();
    $ticket = makeSupportTicket(['status' => 'waiting_customer']);

    $this->actingAs($this->admin)->post(route('admin.support.reply', $ticket), ['message' => 'تحديث بسيط']);

    $ticket->refresh();
    expect($ticket->status)->toBe('waiting_customer');
});

it('an internal note does not notify the customer and is flagged internal', function () {
    Mail::fake();
    $ticket = makeSupportTicket(['status' => 'open']);

    $this->actingAs($this->admin)
        ->post(route('admin.support.reply', $ticket), ['message' => 'ملاحظة داخلية للفريق', 'is_internal' => true])
        ->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->status)->toBe('open'); // unchanged — internal notes don't advance status
    $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'is_internal' => true]);
    Mail::assertNothingSent();
});

it('sends a Telegram reply when the ticket has a linked chat id', function () {
    Mail::fake();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
    config(['services.telegram_support.bot_token' => 'test', 'services.telegram_support.enabled' => true]);

    $ticket = makeSupportTicket(['status' => 'open', 'telegram_chat_id' => '12345']);

    $this->actingAs($this->admin)->post(route('admin.support.reply', $ticket), ['message' => 'رد عبر تيليجرام']);

    Http::assertSent(fn($r) => str_contains($r->url(), 'sendMessage') && str_contains($r['text'], 'تذكرة'));
});

it('reply validates the message is required and bounded', function () {
    $ticket = makeSupportTicket();

    $this->actingAs($this->admin)
        ->post(route('admin.support.reply', $ticket), ['message' => ''])
        ->assertSessionHasErrors('message');

    $this->actingAs($this->admin)
        ->post(route('admin.support.reply', $ticket), ['message' => str_repeat('a', 4001)])
        ->assertSessionHasErrors('message');
});

it('gracefully skips customer notification when the ticket has no linked user', function () {
    Mail::fake();
    $ticket = makeSupportTicket();
    // Detach the user without violating the FK by using a soft/no-op: instead
    // simulate via a ticket whose user was deleted (relation resolves null).
    $userId = $ticket->user_id;
    User::where('id', $userId)->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.support.reply', $ticket), ['message' => 'رد لعميل محذوف'])
        ->assertSessionHas('success');

    Mail::assertNothingSent();
});

it('updates ticket status and sets resolved_at when moving to resolved or closed', function () {
    $ticket = makeSupportTicket(['status' => 'open']);

    $this->actingAs($this->admin)
        ->post(route('admin.support.status', $ticket), ['status' => 'resolved'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->status)->toBe('resolved');
    expect($ticket->resolved_at)->not->toBeNull();
});

it('clears resolved_at when status moves back to an open state', function () {
    $ticket = makeSupportTicket(['status' => 'resolved', 'resolved_at' => now()]);

    $this->actingAs($this->admin)->post(route('admin.support.status', $ticket), ['status' => 'in_progress']);

    $ticket->refresh();
    expect($ticket->status)->toBe('in_progress');
    expect($ticket->resolved_at)->toBeNull();
});

it('rejects an invalid status value', function () {
    $ticket = makeSupportTicket();

    $this->actingAs($this->admin)
        ->post(route('admin.support.status', $ticket), ['status' => 'bogus_status'])
        ->assertSessionHasErrors('status');
});

it('assigns a ticket to the acting admin', function () {
    $ticket = makeSupportTicket();

    $this->actingAs($this->admin)
        ->post(route('admin.support.assign', $ticket))
        ->assertRedirect()
        ->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->assigned_to)->toBe($this->admin->id);
});

it('releases a ticket assignment', function () {
    $ticket = makeSupportTicket(['assigned_to' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.support.assign', $ticket), ['release' => true])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->assigned_to)->toBeNull();
});
