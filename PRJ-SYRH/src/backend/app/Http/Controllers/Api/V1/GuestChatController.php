<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Guest chat — unauthenticated users can start conversations and send messages.
 * Each guest conversation gets a unique token stored in localStorage on the frontend.
 * Token is required for all guest endpoints (acts as auth).
 */
class GuestChatController extends Controller
{
    /**
     * Start a new conversation as a guest (no auth required).
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_id'   => 'required|exists:agencies,id',
            'property_id' => 'nullable|exists:properties,id',
            'client_name' => 'required|string|max:100',
            'client_phone'=> 'nullable|string|max:20',
            'client_email'=> 'nullable|email|max:100',
            'message'     => 'required|string|max:2000',
        ]);

        // Generate unique guest token
        $token = Str::random(40);

        $conversation = Conversation::create([
            'agency_id'    => $validated['agency_id'],
            'property_id'  => $validated['property_id'] ?? null,
            'client_name'  => $validated['client_name'],
            'client_phone' => $validated['client_phone'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'guest_token'  => $token,
        ]);

        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'client',
            'sender_id'       => null,
            'message'         => $validated['message'],
        ]);

        return response()->json([
            'data' => [
                'token'        => $token,
                'conversation' => $conversation->load('agency:id,name,slug,logo_path'),
                'message'      => $msg,
            ],
        ], 201);
    }

    /**
     * Get messages for a guest conversation (identified by token).
     */
    public function messages(string $token): JsonResponse
    {
        $conversation = Conversation::where('guest_token', $token)->firstOrFail();

        $messages = ChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Send a message as a guest.
     */
    public function storeMessage(Request $request, string $token): JsonResponse
    {
        $conversation = Conversation::where('guest_token', $token)->firstOrFail();

        $validated = $request->validate([
            'message'      => 'nullable|string|max:5000',
            'attachment'   => 'nullable|file|mimes:jpeg,png,webp,gif,pdf,doc,docx|max:10240',
            'attachments'  => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,png,webp,gif,pdf,doc,docx|max:10240',
        ]);

        if (!$request->has('message') && !$request->hasFile('attachment') && !$request->hasFile('attachments')) {
            return response()->json(['message' => 'Message or attachment required'], 422);
        }

        $data = [
            'conversation_id' => $conversation->id,
            'sender_type'     => 'client',
            'sender_id'       => null,
            'message'         => $validated['message'] ?? '',
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments', 'public');
            $data['attachment_path'] = $path;
            $data['attachment_type'] = $file->getMimeType();
            $data['attachment_name'] = $file->getClientOriginalName();
            $data['attachment_size'] = $file->getSize();
        }

        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('chat-attachments', 'public');
                $attachments[] = [
                    'path' => $path,
                    'type' => $file->getMimeType(),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
            $data['attachments'] = $attachments;
        }

        $msg = ChatMessage::create($data);
        $conversation->touch();

        return response()->json(['data' => $msg->fresh()], 201);
    }

    /**
     * Mark messages as read (guest side — marks agency messages).
     */
    public function markAsRead(string $token): JsonResponse
    {
        $conversation = Conversation::where('guest_token', $token)->firstOrFail();

        ChatMessage::where('conversation_id', $conversation->id)
            ->where('sender_type', 'agency')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Messages marked as read']);
    }
}
