<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'channel' => ['nullable', 'string', 'max:50'],
            'channel_message_id' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $conversation = Conversation::findOrFail($validated['conversation_id']);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => $validated['type'] ?? 'text',
            'content' => $validated['content'],
            'channel' => $validated['channel'] ?? 'api',
            'channel_message_id' => $validated['channel_message_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json($message, 201);
    }
}
