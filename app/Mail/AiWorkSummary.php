<?php

namespace App\Mail;

use App\Models\GeneratedReport;
use App\Models\Organization;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AiWorkSummary extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public GeneratedReport $report,
    ) {}

    public function build(): self
    {
        $subject = ucfirst($this->report->period).' AI Work Summary — '.$this->organization->name;

        return $this
            ->subject($subject)
            ->markdown('emails.ai-work-summary', [
                'organization' => $this->organization,
                'report' => $this->report,
                'symbol' => Currency::symbol($this->organization->currency),
            ]);
    }
}
