@component('mail::message')
# {{ ucfirst($report->period) }} AI Work Summary — {{ $organization->name }}

{{ $report->summary_text }}

## Key metrics

- **Conversations:** {{ $report->metrics['conversations'] ?? 0 }}
- **Inquiries:** {{ $report->metrics['inquiries'] ?? 0 }}
- **New customers:** {{ $report->metrics['new_customers'] ?? 0 }}
- **Leads:** {{ $report->metrics['leads'] ?? 0 }}
- **Orders:** {{ $report->metrics['orders'] ?? 0 }}
- **Revenue:** {{ $symbol }}{{ number_format((float) ($report->metrics['revenue'] ?? 0), 2) }}
- **Escalations:** {{ $report->metrics['escalations'] ?? 0 }}
- **AI resolution rate:** {{ $report->metrics['ai_resolution_rate'] ?? 0 }}%
- **AI runs:** {{ $report->metrics['ai_runs'] ?? 0 }}
- **AI cost:** {{ $symbol }}{{ number_format((float) ($report->metrics['ai_cost'] ?? 0), 2) }}
- **Open knowledge gaps:** {{ $report->metrics['open_knowledge_gaps'] ?? 0 }}

@component('mail::button', ['url' => url('/reports')])
View full report
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
