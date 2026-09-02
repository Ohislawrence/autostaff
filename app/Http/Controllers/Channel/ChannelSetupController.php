<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Models\AiEmployee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChannelSetupController extends Controller
{
    public function setup(AiEmployee $aiEmployee)
    {
        $orgId = current_org_id();
        if ($aiEmployee->organization_id !== $orgId) abort(403);

        return Inertia::render('Channels/Setup', [
            'employee' => $aiEmployee,
            'channels' => [
                [
                    'id' => 'web_chat', 'name' => 'Website Chat Widget',
                    'description' => 'Embed a chat widget on your website so visitors can talk to your AI employee.',
                    'icon' => '💬', 'connected' => in_array('web_chat', $aiEmployee->allowed_channels ?? ['web_chat']),
                    'snippet' => sprintf('<script src="%s/widget/chat-widget.js" data-employee="%s" data-greeting="%s" data-color="#4F46E5"></script>', config('app.url'), $aiEmployee->uuid, urlencode("👋 Hi! How can we help you today?")),
                ],
                ['id' => 'whatsapp', 'name' => 'WhatsApp', 'description' => 'Connect WhatsApp Business API.', 'icon' => '📱', 'connected' => in_array('whatsapp', $aiEmployee->allowed_channels ?? []), 'requires_config' => true],
                ['id' => 'email', 'name' => 'Email', 'description' => 'Handle customer inquiries via email. Configure SMTP and your AI will respond to incoming messages.', 'icon' => '📧', 'connected' => in_array('email', $aiEmployee->allowed_channels ?? []), 'requires_config' => true, 'integration' => 'email'],
                ['id' => 'api', 'name' => 'REST API', 'description' => 'Integrate via API.', 'icon' => '🔌', 'connected' => true, 'status' => 'available'],
            ],
        ]);
    }

    public function toggleChannel(Request $request, AiEmployee $aiEmployee)
    {
        $orgId = current_org_id();
        if ($aiEmployee->organization_id !== $orgId) abort(403);

        $request->validate(['channel' => 'required|string|in:web_chat,whatsapp,email']);
        $allowed = $aiEmployee->allowed_channels ?? ['web_chat'];
        $channel = $request->channel;

        if (in_array($channel, $allowed)) {
            $allowed = array_values(array_diff($allowed, [$channel]));
        } else {
            $allowed[] = $channel;
        }

        $aiEmployee->update(['allowed_channels' => $allowed]);
        return back()->with('success', 'Channel updated.');
    }
}