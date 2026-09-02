<?php

namespace App\Services;

use App\Models\Conversation;

/**
 * Builds a structured AI → human handoff payload.
 *
 * A proper handoff is not a single sentence — it's a complete context packet:
 * customer profile, related orders, actions the AI already took, escalation
 * reason, and a recommended next action. This is stored on the conversation so
 * any agent picking it up immediately has full context.
 */
class HandoffService
{
    public function build(Conversation $conversation, string $reason, ?string $summary = null): array
    {
        $customer = $conversation->customer;

        $payload = [
            'reason' => $reason,
            'summary' => $summary ?? $this->summarize($conversation),
            'customer' => $this->customer($customer),
            'related_orders' => $this->orders($customer),
            'related_quotations' => $this->quotations($customer),
            'related_invoices' => $this->invoices($customer),
            'ai_actions' => $this->toolExecutions($conversation),
            'conversation_turns' => $conversation->messages()->count(),
            'handed_off_at' => now()->toISOString(),
        ];

        $metadata = $conversation->metadata ?? [];
        $metadata['handoff'] = $payload;

        $conversation->update([
            'metadata' => $metadata,
            'status' => 'human_required',
            'priority' => $this->detectPriority($conversation),
        ]);

        return $payload;
    }

    protected function summarize(Conversation $conversation): string
    {
        $recent = $conversation->messages()
            ->whereIn('type', ['incoming', 'ai_response', 'human_response'])
            ->latest()
            ->take(6)
            ->get(['type', 'content'])
            ->reverse();

        $lines = [];
        foreach ($recent as $m) {
            $who = $m->type === 'incoming' ? 'Customer' : 'Agent';
            $lines[] = "{$who}: " . mb_substr((string) $m->content, 0, 300);
        }

        return implode("\n", $lines);
    }

    protected function customer($customer): array
    {
        if (! $customer) {
            return null;
        }

        return [
            'id' => $customer->id,
            'name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'email' => $customer->email,
            'phone' => $customer->phone,
            'lead_stage' => $customer->lead_stage,
            'lead_score' => $customer->lead_score,
        ];
    }

    protected function orders($customer): array
    {
        if (! $customer) {
            return [];
        }

        return $customer->orders()->latest()->limit(5)->get()
            ->map(fn ($o) => [
                'order_number' => $o->order_number,
                'status' => $o->status,
                'total' => (string) $o->total,
                'payment_status' => $o->payment_status,
            ])
            ->values()
            ->all();
    }

    protected function quotations($customer): array
    {
        if (! $customer) {
            return [];
        }

        try {
            return \Illuminate\Support\Facades\DB::table('quotations')
                ->where('customer_id', $customer->id)
                ->latest('id')->limit(5)->get()
                ->map(fn ($q) => ['id' => $q->id, 'status' => $q->status, 'total' => (string) $q->total])
                ->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function invoices($customer): array
    {
        if (! $customer) {
            return [];
        }

        try {
            return \Illuminate\Support\Facades\DB::table('invoices')
                ->where('customer_id', $customer->id)
                ->latest('id')->limit(5)->get()
                ->map(fn ($i) => ['id' => $i->id, 'status' => $i->status, 'total' => (string) $i->total])
                ->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function toolExecutions(Conversation $conversation): array
    {
        return $conversation->toolExecutions()
            ->with('tool')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'tool' => $t->tool?->name ?? 'Unknown',
                'status' => $t->status,
                'at' => $t->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    protected function detectPriority(Conversation $conversation): string
    {
        $metadata = $conversation->metadata ?? [];

        // Low-confidence understanding or negative sentiment → high priority.
        $understanding = $metadata['understanding'] ?? [];
        if (($understanding['sentiment'] ?? null) === 'negative') {
            return 'high';
        }
        if (($understanding['urgency'] ?? null) === 'high') {
            return 'urgent';
        }

        return 'high';
    }
}