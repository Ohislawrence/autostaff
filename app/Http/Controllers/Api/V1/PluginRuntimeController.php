<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class PluginRuntimeController extends Controller
{
    /**
     * Called by installed plugins to report in and update "last seen".
     */
    public function heartbeat()
    {
        $installation = current_plugin_installation();

        if ($installation) {
            $installation->update(['last_seen_at' => now()]);
        }

        return response()->json([
            'status' => 'ok',
            'time' => now()->toISOString(),
        ]);
    }
}
