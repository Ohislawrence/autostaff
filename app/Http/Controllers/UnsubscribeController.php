<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Services\Prospecting\SuppressionService;

class UnsubscribeController extends Controller
{
    public function __invoke(string $token, SuppressionService $suppression)
    {
        $prospect = Prospect::where('unsubscribe_token', $token)->first();

        if ($prospect) {
            $suppression->suppress($prospect->email, 'unsubscribe', $prospect->organization_id, $prospect->campaign_id, 'link');
            $prospect->update([
                'status' => 'unsubscribed',
                'suppressed_at' => now(),
                'suppression_reason' => 'unsubscribe',
            ]);
            $prospect->events()->create([
                'organization_id' => $prospect->organization_id,
                'type' => 'unsubscribed',
                'payload' => ['via' => 'link'],
            ]);
        }

        $message = $prospect
            ? 'You have been unsubscribed from future outreach emails.'
            : 'This unsubscribe link is invalid or has expired.';

        return response(
            '<html><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:90vh;background:#f9fafb;"><div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:40px;max-width:420px;text-align:center;"><h1 style="font-size:20px;margin:0 0 12px;">' . ($prospect ? 'Unsubscribed ✓' : 'Link not found') . '</h1><p style="color:#6b7280;">' . $message . '</p></div></body></html>'
        )->header('Content-Type', 'text/html');
    }
}
