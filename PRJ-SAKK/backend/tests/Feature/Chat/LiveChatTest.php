<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Live-chat module — customer API (polling) ↔ admin inbox/thread.
 * Standalone, independent of the SupportTicket desk.
 */

// ─────────────────────────── customer side ───────────────────────────

it('lazily opens a conversation and stores a customer message', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    test()->getJson('/api/v1/chat/conversation')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Conversation::where('user_id', $user->id)->where('status', 'open')->count())->toBe(1);

    $send = test()->postJson('/api/v1/chat/messages', ['body' => 'مرحباً، لدي استفسار'])
        ->assertCreated()
        ->assertJsonPath('data.sender_type', 'user');

    $conv = Conversation::where('user_id', $user->id)->first();
    expect(ChatMessage::where('conversation_id', $conv->id)->where('sender_type', 'user')->where('body', 'مرحباً، لدي استفسار')->exists())->toBeTrue();
});

it('polls only messages after a given id', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $m1 = test()->postJson('/api/v1/chat/messages', ['body' => 'أولى'])->json('data.id');
    test()->postJson('/api/v1/chat/messages', ['body' => 'ثانية']);

    $after = test()->getJson('/api/v1/chat/messages?after=' . $m1)->assertOk();
    $bodies = collect($after->json('data.messages'))->pluck('body');
    expect($bodies)->toContain('ثانية')->not->toContain('أولى');
});

it('rejects an empty chat message', function () {
    Sanctum::actingAs(User::factory()->create());
    test()->postJson('/api/v1/chat/messages', ['body' => ''])->assertStatus(422);
});

// ─────────────────────────── admin inbox/thread ───────────────────────────

function chatAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

it('shows the customer full name in the admin inbox feed — not the numeric id', function () {
    $customer = User::factory()->create(['first_name' => 'سامر', 'last_name' => 'الحلبي']);
    $conv = Conversation::create(['user_id' => $customer->id, 'status' => 'open', 'last_message_at' => now()]);
    $conv->messages()->create(['sender_type' => 'user', 'sender_id' => $customer->id, 'body' => 'السلام عليكم']);

    $feed = test()->actingAs(chatAdmin())->getJson(route('admin.chat.feed'))->assertOk();

    $row = collect($feed->json('data.conversations'))->firstWhere('id', $conv->id);
    expect($row['user_name'])->toBe('سامر الحلبي')
        ->and($row['user_name'])->not->toBe('#' . $customer->id)
        ->and($row['last_body'])->toBe('السلام عليكم')
        ->and($row['unread'])->toBe(1);
});

it('claims an unassigned thread and greets the customer exactly once', function () {
    $admin = chatAdmin();
    $customer = User::factory()->create();
    $conv = Conversation::create(['user_id' => $customer->id, 'status' => 'open', 'last_message_at' => now()]);

    test()->actingAs($admin)->get(route('admin.chat.show', $conv))->assertOk();
    test()->actingAs($admin)->get(route('admin.chat.show', $conv))->assertOk();

    expect($conv->fresh()->agent_id)->toBe($admin->id);
    expect(ChatMessage::where('conversation_id', $conv->id)->where('sender_type', 'system')->count())->toBe(1);
});

it('marks customer messages read once the agent opens the thread', function () {
    $customer = User::factory()->create();
    $conv = Conversation::create(['user_id' => $customer->id, 'status' => 'open', 'last_message_at' => now()]);
    $conv->messages()->create(['sender_type' => 'user', 'sender_id' => $customer->id, 'body' => 'غير مقروءة']);

    test()->actingAs(chatAdmin())->get(route('admin.chat.show', $conv))->assertOk();

    expect(ChatMessage::where('conversation_id', $conv->id)->where('sender_type', 'user')->whereNull('read_at')->count())->toBe(0);
});

it('delivers an agent reply to the customer poll', function () {
    $admin = chatAdmin();
    $customer = User::factory()->create();
    $conv = Conversation::create(['user_id' => $customer->id, 'status' => 'open', 'last_message_at' => now()]);

    test()->actingAs($admin)->postJson(route('admin.chat.reply', $conv), ['body' => 'كيف أساعدك؟'])
        ->assertCreated()
        ->assertJsonPath('data.sender_type', 'agent');

    Sanctum::actingAs($customer);
    $poll = test()->getJson('/api/v1/chat/messages')->assertOk();
    expect(collect($poll->json('data.messages'))->pluck('body'))->toContain('كيف أساعدك؟');
});
