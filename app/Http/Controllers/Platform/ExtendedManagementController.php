<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AiRun;
use App\Models\KnowledgeSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExtendedManagementController extends Controller
{
    // Knowledge Processing Monitor
    public function knowledgeProcessing(Request $request)
    {
        $sources = KnowledgeSource::with(['knowledgeBase', 'organization'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => KnowledgeSource::count(),
            'completed' => KnowledgeSource::where('status', 'completed')->count(),
            'processing' => KnowledgeSource::where('status', 'processing')->count(),
            'failed' => KnowledgeSource::where('status', 'failed')->count(),
            'pending' => KnowledgeSource::where('status', 'pending')->count(),
        ];

        return Inertia::render('Platform/KnowledgeProcessing', [
            'sources' => $sources,
            'stats' => $stats,
            'filters' => $request->only(['status']),
        ]);
    }

    // Integrations Management
    public function integrations()
    {
        $integrations = [
            ['name' => 'WhatsApp', 'key' => 'whatsapp', 'status' => !empty(env('WHATSAPP_API_KEY')) ? 'connected' : 'not_configured', 'icon' => '📱'],
            ['name' => 'DeepSeek', 'key' => 'deepseek', 'status' => !empty(env('DEEPSEEK_API_KEY')) ? 'connected' : 'not_configured', 'icon' => '🤖'],
            ['name' => 'Email (SMTP)', 'key' => 'email', 'status' => config('mail.mailer') !== 'log' ? 'connected' : 'not_configured', 'icon' => '📧'],
            ['name' => 'Storage (S3)', 'key' => 'storage', 'status' => !empty(env('AWS_ACCESS_KEY_ID')) ? 'connected' : 'not_configured', 'icon' => '☁️'],
            ['name' => 'Paystack', 'key' => 'paystack', 'status' => !empty(env('PAYSTACK_SECRET_KEY')) ? 'connected' : 'not_configured', 'icon' => '💳'],
            ['name' => 'Google Calendar', 'key' => 'google', 'status' => !empty(env('GOOGLE_CLIENT_ID')) ? 'connected' : 'not_configured', 'icon' => '📅'],
        ];

        return Inertia::render('Platform/Integrations', ['integrations' => $integrations]);
    }

    // Announcements
    public function announcements()
    {
        $announcements = DB::table('platform_announcements')->latest()->get();

        return Inertia::render('Platform/Announcements', ['announcements' => $announcements]);
    }

    public function storeAnnouncement(Request $request)
    {
        DB::table('platform_announcements')->insert([
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type ?? 'info',
            'send_email' => $request->boolean('send_email'),
            'send_in_app' => $request->boolean('send_in_app', true),
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Announcement published.');
    }

    // Support Tickets
    public function tickets(Request $request)
    {
        $tickets = DB::table('support_tickets')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return Inertia::render('Platform/Tickets', [
            'tickets' => $tickets,
            'filters' => $request->only(['status']),
        ]);
    }

    // AI Evaluations
    public function evaluations()
    {
        $evaluations = DB::table('ai_evaluations')
            ->when(request('category'), fn ($q) => $q->where('category', request('category')))
            ->latest()
            ->paginate(20);

        return Inertia::render('Platform/Evaluations', [
            'evaluations' => $evaluations,
            'categories' => ['correctness', 'knowledge_retrieval', 'tool_selection', 'policy_compliance', 'escalation', 'hallucination'],
            'filters' => request()->only(['category']),
        ]);
    }

    public function storeEvaluation(Request $request)
    {
        DB::table('ai_evaluations')->insert([
            'organization_id' => 0, // global evaluation
            'category' => $request->category,
            'input' => $request->input,
            'expected_output' => $request->expected_output,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Test case added.');
    }
}