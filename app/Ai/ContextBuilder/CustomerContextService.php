<?php

namespace App\Ai\ContextBuilder;

use App\Models\Customer;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;

/**
 * Builds the "IDENTIFY" + "LOAD CONTEXT" portion of the AI Employee pipeline.
 *
 * Given a customer, it assembles a compact but rich snapshot of who they are
 * (identity) and their business lifecycle (orders, quotations, invoices,
 * payments, leads, tickets) so the AI behaves like an employee who knows the
 * customer — not a stateless chatbot.
 */
class CustomerContextService
{
    public function build(Customer $customer): array
    {
        return [
            'identity' => $this->identity($customer),
            'orders' => $this->orders($customer),
            'quotations' => $this->quotations($customer),
            'invoices' => $this->invoices($customer),
            'payments' => $this->payments($customer),
            'leads' => $this->leads($customer),
            'tickets' => $this->tickets($customer),
            'memory' => $this->memory($customer),
        ];
    }

    public function toPromptString(array $context, ?string $currency = null): string
    {
        $symbol = Currency::symbol($currency);
        $sections = [];

        $identity = $context['identity'] ?? [];
        if (! empty($identity)) {
            $lines = ["CUSTOMER PROFILE:"];
            foreach ($identity as $key => $value) {
                if ($value !== null && $value !== '') {
                    $lines[] = "- $key: $value";
                }
            }
            $sections[] = implode("\n", $lines);
        }

        $orders = $context['orders'] ?? [];
        if (! empty($orders)) {
            $lines = ["ORDER HISTORY (" . count($orders) . " total):"];
            foreach ($orders as $o) {
                $lines[] = "- {$o['order_number']} · {$o['status']} · {$symbol}{$o['total']} · {$o['payment_status']}";
            }
            $sections[] = implode("\n", $lines);
        }

        $quotations = $context['quotations'] ?? [];
        if (! empty($quotations)) {
            $lines = ["QUOTATIONS:"];
            foreach ($quotations as $q) {
                $lines[] = "- QTN-{$q['id']} · {$q['status']} · {$symbol}{$q['total']}";
            }
            $sections[] = implode("\n", $lines);
        }

        $invoices = $context['invoices'] ?? [];
        if (! empty($invoices)) {
            $lines = ["INVOICES:"];
            foreach ($invoices as $i) {
                $lines[] = "- INV-{$i['id']} · {$i['status']} · {$symbol}{$i['total']}";
            }
            $sections[] = implode("\n", $lines);
        }

        $payments = $context['payments'] ?? [];
        if (! empty($payments)) {
            $lines = ["PAYMENTS:"];
            foreach ($payments as $p) {
                $lines[] = "- {$p['status']} · {$symbol}{$p['amount']} · {$p['paid_at']}";
            }
            $sections[] = implode("\n", $lines);
        }

        $leads = $context['leads'] ?? [];
        if (! empty($leads)) {
            $lines = ["LEADS:"];
            foreach ($leads as $l) {
                $interest = $l['product_interest'] ? " ({$l['product_interest']})" : '';
                $lines[] = "- {$l['stage']}{$interest}";
            }
            $sections[] = implode("\n", $lines);
        }

        $tickets = $context['tickets'] ?? [];
        if (! empty($tickets)) {
            $lines = ["SUPPORT TICKETS:"];
            foreach ($tickets as $t) {
                $lines[] = "- {$t['status']}: {$t['subject']}";
            }
            $sections[] = implode("\n", $lines);
        }

        $memory = $context['memory'] ?? [];
        if (! empty($memory)) {
            $sections[] = "CUSTOMER MEMORY:\n" . $memory;
        }

        return implode("\n\n", $sections);
    }

    protected function identity(Customer $customer): array
    {
        return [
            'name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company' => $customer->company,
            'lead_stage' => $customer->lead_stage,
            'lead_score' => $customer->lead_score,
            'notes' => $customer->notes,
            'tags' => is_array($customer->tags) ? implode(', ', $customer->tags) : $customer->tags,
        ];
    }

    protected function orders(Customer $customer): array
    {
        return $customer->orders()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($o) => [
                'order_number' => $o->order_number,
                'status' => $o->status,
                'total' => (string) $o->total,
                'currency' => $o->currency,
                'payment_status' => $o->payment_status,
                'created_at' => $o->created_at?->toDateString(),
            ])
            ->values()
            ->all();
    }

    protected function quotations(Customer $customer): array
    {
        try {
            return DB::table('quotations')
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn ($q) => [
                    'id' => $q->id,
                    'status' => $q->status,
                    'total' => (string) $q->total,
                    'currency' => $q->currency,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function invoices(Customer $customer): array
    {
        try {
            return DB::table('invoices')
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'status' => $i->status,
                    'total' => (string) $i->total,
                    'currency' => $i->currency,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function payments(Customer $customer): array
    {
        try {
            return DB::table('payments')
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn ($p) => [
                    'status' => $p->status,
                    'amount' => (string) $p->amount,
                    'currency' => $p->currency,
                    'paid_at' => $p->paid_at ? substr((string) $p->paid_at, 0, 10) : null,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function leads(Customer $customer): array
    {
        return $customer->leads()
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($l) => [
                'stage' => $l->stage,
                'product_interest' => $l->product_interest,
                'score' => $l->score,
            ])
            ->values()
            ->all();
    }

    protected function tickets(Customer $customer): array
    {
        try {
            return DB::table('tickets')
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->limit(3)
                ->get()
                ->map(fn ($t) => [
                    'status' => $t->status ?? 'open',
                    'subject' => $t->subject ?? $t->title ?? 'Support ticket',
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function memory(Customer $customer): string
    {
        $summary = $customer->ai_summary;
        if (empty($summary) || ! is_array($summary)) {
            return '';
        }

        $parts = [];

        if (isset($summary['last_interaction'])) {
            $parts[] = "Last interaction: {$summary['last_interaction']}";
        }

        $history = $summary['history'] ?? [];
        if (is_array($history) && count($history) > 0) {
            $recent = end($history);
            if (is_array($recent)) {
                $topics = $recent['topics'] ?? [];
                if (! empty($topics)) {
                    $parts[] = 'Recently discussed: ' . implode(', ', $topics);
                }
                $parts[] = 'Previous conversations: ' . count($history);
            }
        }

        return implode('. ', $parts);
    }
}