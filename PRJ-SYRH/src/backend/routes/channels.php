<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('conversation.{id}', function ($user, $id) {
    $conversation = Conversation::find($id);
    if (!$conversation) return false;
    if ($user->hasRole(['agency', 'admin'])) {
        return $conversation->agency_id && $user->agency_id === $conversation->agency_id;
    }
    return $conversation->user_id === $user->id;
});
