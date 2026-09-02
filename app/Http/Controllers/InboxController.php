<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $organization = current_org();

        $conversations = $organization->conversations()
            ->with(['customer', 'aiEmployee', 'latestMessage'])
            ->withCount('messages')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhereHas('customer', fn ($q) => $q->where('first_name', 'like', "%{$request->search}%")->orWhere('last_name', 'like', "%{$request->search}%"))
            ))
            ->latest('last_message_at')
            ->paginate(20);

        // Conversation counts for filter badges
        $counts = [
            'all' => $organization->conversations()->count(),
            'open' => $organization->conversations()->whereIn('status', ['open', 'ai_handling'])->count(),
            'human_required' => $organization->conversations()->where('status', 'human_required')->count(),
            'waiting' => $organization->conversations()->where('status', 'waiting_customer')->count(),
            'resolved' => $organization->conversations()->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return Inertia::render('Inbox/Index', [
            'conversations' => $conversations,
            'counts' => $counts,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Conversation $conversation)
    {
        $orgId = current_org_id();
        if ($conversation->organization_id !== $orgId) {
            abort(403);
        }

        $conversation->load([
            'customer',
            'aiEmployee',
            'messages' => fn ($q) => $q->with('attachments')->oldest(),
        ]);

        return Inertia::render('Inbox/Show', [
            'conversation' => $conversation,
        ]);
    }

    public function sendReply(Request $request, Conversation $conversation)
    {
        $orgId = current_org_id();
        if ($conversation->organization_id !== $orgId) {
            abort(403);
        }

        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Create a human response message
        $message = $conversation->messages()->create([
            'organization_id' => $orgId,
            'type' => 'human_response',
            'content' => $request->body,
            'sender_type' => 'user',
            'sender_id' => auth()->id(),
            'metadata' => json_encode([
                'user_name' => auth()->user()->name,
                'user_role' => auth()->user()->getRoleNames()->first(),
            ]),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'status' => 'waiting_customer',
        ]);

        return back()->with('success', 'Reply sent.');
    }

    public function resolve(Conversation $conversation)
    {
        $orgId = current_org_id();
        if ($conversation->organization_id !== $orgId) {
            abort(403);
        }

        $conversation->update(['status' => 'resolved']);

        return back()->with('success', 'Conversation resolved.');
    }

    public function assign(Conversation $conversation)
    {
        $orgId = current_org_id();
        if ($conversation->organization_id !== $orgId) {
            abort(403);
        }

        $conversation->update([
            'status' => 'assigned',
            'assigned_to' => auth()->id(),
        ]);

        return back()->with('success', 'Conversation assigned to you.');
    }
}