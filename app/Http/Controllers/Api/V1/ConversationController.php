<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::with(['customer', 'aiEmployee'])
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($conversations);
    }

    public function show(Conversation $conversation)
    {
        abort_unless($conversation->organization_id === current_org_id(), 404);

        return response()->json($conversation->load(['customer', 'aiEmployee', 'messages']));
    }
}
