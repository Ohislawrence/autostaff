<?php

namespace App\Jobs\Prospecting;

use App\Services\Prospecting\ProspectFollowupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProspectFollowupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(protected ?int $campaignId = null) {}

    public function handle(ProspectFollowupService $service): void
    {
        $service->run($this->campaignId);
    }
}
