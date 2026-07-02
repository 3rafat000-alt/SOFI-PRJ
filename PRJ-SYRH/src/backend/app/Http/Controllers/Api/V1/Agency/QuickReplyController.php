<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\QuickReply;
use App\Models\Property;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuickReplyController extends Controller
{
    /**
     * Wrap broadcast event in try-catch so Reverb failure never crashes HTTP response.
     */
    private function tryBroadcast($event): void
    {
        try {
            event($event);
        } catch (\Pusher\PusherException $e) {
            logger()->warning('Broadcast failed (message saved, real-time delayed): ' . $e->getMessage());
        } catch (\Throwable $e) {
            logger()->warning('Broadcast failed: ' . $e->getMessage());
        }
    }

    private function getAgencyId(): int
    {
        return auth()->user()->agency->id ?? auth()->user()->agent->agency_id;
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $this->getAgencyId();

        $query = QuickReply::where('agency_id', $agencyId);

        // Filter by property if specified
        if ($request->filled('property_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('property_id', $request->property_id)
                  ->orWhereNull('property_id');
            });
        }

        // General-only (no property filter)
        if ($request->boolean('general_only')) {
            $query->whereNull('property_id');
        }

        $replies = $query->orderBy('sort_order')->orderBy('title')->get();

        return response()->json(['data' => $replies]);
    }

    public function store(Request $request): JsonResponse
    {
        $agencyId = $this->getAgencyId();

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'required|string|max:5000',
            'property_id' => 'nullable|exists:properties,id',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        // Make sure property belongs to this agency
        if (!empty($validated['property_id'])) {
            $prop = Property::find($validated['property_id']);
            if (!$prop || $prop->agency_id !== $agencyId) {
                return response()->json(['message' => 'العقار غير تابع لهذه الوكالة'], 422);
            }
        }

        $validated['agency_id'] = $agencyId;
        $validated['sort_order'] ??= 0;

        // Auto-detect placeholders from content
        $reply = QuickReply::create($validated);
        $reply->placeholders = $reply->availablePlaceholders();
        $reply->save();

        return response()->json(['data' => $reply, 'message' => 'تم إضافة الرد الجاهز'], 201);
    }

    public function show(QuickReply $quickReply): JsonResponse
    {
        if ($quickReply->agency_id !== $this->getAgencyId()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        return response()->json(['data' => $quickReply]);
    }

    public function update(Request $request, QuickReply $quickReply): JsonResponse
    {
        if ($quickReply->agency_id !== $this->getAgencyId()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'content'     => 'sometimes|required|string|max:5000',
            'property_id' => 'nullable|exists:properties,id',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        if (array_key_exists('property_id', $validated) && !empty($validated['property_id'])) {
            $prop = Property::find($validated['property_id']);
            if (!$prop || $prop->agency_id !== $this->getAgencyId()) {
                return response()->json(['message' => 'العقار غير تابع لهذه الوكالة'], 422);
            }
        }

        $quickReply->update($validated);

        // Re-scan placeholders if content changed
        if (isset($validated['content'])) {
            $quickReply->placeholders = $quickReply->availablePlaceholders();
            $quickReply->save();
        }

        return response()->json(['data' => $quickReply->fresh(), 'message' => 'تم تحديث الرد الجاهز']);
    }

    public function destroy(QuickReply $quickReply): JsonResponse
    {
        if ($quickReply->agency_id !== $this->getAgencyId()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $quickReply->delete();

        return response()->json(['message' => 'تم حذف الرد الجاهز']);
    }

    /**
     * Preview a rendered reply with sample values.
     */
    public function preview(Request $request, QuickReply $quickReply): JsonResponse
    {
        if ($quickReply->agency_id !== $this->getAgencyId()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $values = $request->input('values', []);

        $rendered = $quickReply->render($values);

        return response()->json(['data' => [
            'id'       => $quickReply->id,
            'title'    => $quickReply->title,
            'rendered' => $rendered,
        ]]);
    }

    /**
     * Send a quick reply to a conversation.
     */
    public function send(Request $request, QuickReply $quickReply): JsonResponse
    {
        $agencyId = $this->getAgencyId();

        if ($quickReply->agency_id !== $agencyId) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'values'          => 'nullable|array',
        ]);

        $conv = Conversation::findOrFail($validated['conversation_id']);

        if ($conv->agency_id !== $agencyId) {
            return response()->json(['message' => 'المحادثة غير تابعة لهذه الوكالة'], 403);
        }

        // Build substitution values
        $values = $validated['values'] ?? [];

        // Auto-fill conversation context
        if (!isset($values['client_name']) && $conv->client_name) {
            $values['client_name'] = $conv->client_name;
        }
        if ($conv->property) {
            if (!isset($values['property_title'])) {
                $values['property_title'] = $conv->property->title_ar;
            }
            if (!isset($values['price'])) {
                $values['price'] = number_format($conv->property->price) . ' ' . $conv->property->currency;
            }
            if (!isset($values['area'])) {
                $values['area'] = $conv->property->area_sqm;
            }
            if (!isset($values['bedrooms'])) {
                $values['bedrooms'] = $conv->property->bedrooms;
            }
            if (!isset($values['location'])) {
                $values['location'] = $conv->property->address_ar;
            }
        }

        $rendered = $quickReply->render($values);

        // Send as agency message
        $senderType = auth()->user()->agent ? 'agent' : 'agency';
        $senderId = auth()->id();

        $message = $conv->messages()->create([
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'message'     => $rendered,
            'message_type'=> 'quick_reply',
        ]);

        $this->tryBroadcast(new NewMessage($message, $conv->id, 'agency'));

        return response()->json([
            'data'    => $message,
            'message' => 'تم إرسال الرد الجاهز',
        ], 201);
    }
}
