<?php

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Support bot (@SakkSupportBot) ↔ ticket desk bridge.
 */

function enableSupport(string $secret = 'support-secret'): void
{
    config()->set('services.telegram_support.enabled', true);
    config()->set('services.telegram_support.bot_token', 'sup:TESTTOKEN');
    config()->set('services.telegram_support.webhook_secret', $secret);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
}

function supportUser(string $chatId = '700700'): User
{
    return User::factory()->create(['telegram_chat_id' => $chatId]);
}

function inbound(int $chatId, string $text, array $headers = []): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/v1/telegram/support/webhook', [
        'message' => ['chat' => ['id' => $chatId], 'from' => ['username' => 'cust'], 'text' => $text],
    ], array_merge(['X-Telegram-Bot-Api-Secret-Token' => 'support-secret'], $headers));
}

it('opens a support ticket from a linked user message and replies', function () {
    enableSupport();
    $user = supportUser('700700');

    inbound(700700, 'لا أستطيع سحب رصيدي')->assertOk();

    $ticket = SupportTicket::where('telegram_chat_id', '700700')->first();
    expect($ticket)->not->toBeNull()
        ->and($ticket->user_id)->toBe($user->id)
        ->and($ticket->status)->toBe('open');
    expect(TicketMessage::where('ticket_id', $ticket->id)->where('message', 'لا أستطيع سحب رصيدي')->exists())->toBeTrue();
    Http::assertSent(fn ($r) => str_contains($r->url(), '/sendMessage'));
});

it('appends to the same open ticket on a second message', function () {
    enableSupport();
    supportUser('700700');

    inbound(700700, 'رسالة أولى')->assertOk();
    inbound(700700, 'رسالة ثانية')->assertOk();

    expect(SupportTicket::where('telegram_chat_id', '700700')->count())->toBe(1);
    $ticket = SupportTicket::where('telegram_chat_id', '700700')->first();
    expect(TicketMessage::where('ticket_id', $ticket->id)->count())->toBe(2);
});

it('does not open a ticket for an unlinked chat', function () {
    enableSupport();

    inbound(999111, 'مرحبا')->assertOk();

    expect(SupportTicket::where('telegram_chat_id', '999111')->exists())->toBeFalse();
    Http::assertSent(fn ($r) => str_contains($r->url(), '/sendMessage')); // got the "link first" reply
});

it('rejects support webhook calls with a wrong secret', function () {
    enableSupport('right-secret');
    supportUser('700700');

    inbound(700700, 'مشكلة', ['X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret'])->assertOk();

    expect(SupportTicket::where('telegram_chat_id', '700700')->exists())->toBeFalse();
});

it('pushes a public agent reply back to the telegram chat', function () {
    enableSupport();
    Mail::fake();
    $user = supportUser('700700');
    $ticket = SupportTicket::create([
        'uuid' => (string) Str::uuid(), 'user_id' => $user->id, 'ticket_number' => 'TK-TEST-0001',
        'subject' => 'تلجرام: اختبار', 'description' => 'x', 'category' => 'general',
        'priority' => 'medium', 'status' => 'open', 'telegram_chat_id' => '700700',
    ]);

    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    test()->actingAs($admin)
        ->from(route('admin.support.show', $ticket))
        ->post(route('admin.support.reply', $ticket), ['message' => 'تم حل مشكلتك']);

    Http::assertSent(fn ($r) => str_contains($r->url(), '/sendMessage'));
});

it('does not push internal notes to telegram', function () {
    enableSupport();
    Mail::fake();
    $user = supportUser('700700');
    $ticket = SupportTicket::create([
        'uuid' => (string) Str::uuid(), 'user_id' => $user->id, 'ticket_number' => 'TK-TEST-0002',
        'subject' => 'تلجرام: اختبار', 'description' => 'x', 'category' => 'general',
        'priority' => 'medium', 'status' => 'open', 'telegram_chat_id' => '700700',
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    test()->actingAs($admin)
        ->from(route('admin.support.show', $ticket))
        ->post(route('admin.support.reply', $ticket), ['message' => 'ملاحظة داخلية', 'is_internal' => '1']);

    Http::assertNotSent(fn ($r) => str_contains($r->url(), '/sendMessage'));
});
