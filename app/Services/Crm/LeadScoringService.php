<?php

namespace App\Services\Crm;

use App\Models\Customer;

class LeadScoringService
{
    /**
     * Calculate a lead score for a customer based on their interactions and signals.
     * Returns the total score and a breakdown of contributing factors.
     */
    public function calculate(Customer $customer): array
    {
        $breakdown = [];
        $total = 0;

        // 1. Contact completeness signals
        if ($customer->email) {
            $breakdown['email_provided'] = 5;
            $total += 5;
        }
        if ($customer->phone) {
            $breakdown['phone_provided'] = 5;
            $total += 5;
        }
        if ($customer->company) {
            $breakdown['company_provided'] = 5;
            $total += 5;
        }

        // 2. Engagement signals
        $conversationCount = $customer->conversations()->count();
        if ($conversationCount > 0) {
            $score = min(10, $conversationCount * 2);
            $breakdown['conversations'] = $score;
            $total += $score;
        }

        $messageCount = \App\Models\Message::where('customer_id', $customer->id)
            ->where('type', 'incoming')
            ->count();
        if ($messageCount > 0) {
            $score = min(15, $messageCount * 1);
            $breakdown['messages_sent'] = $score;
            $total += $score;
        }

        // 3. Purchase intent signals
        $hasLeads = $customer->leads()->count();
        if ($hasLeads > 0) {
            $breakdown['has_leads'] = 10;
            $total += 10;
        }

        $hasOrders = $customer->orders()->count();
        if ($hasOrders > 0) {
            $breakdown['previous_purchases'] = 15;
            $total += 15;
        }

        // 4. Recency signals
        if ($customer->last_contacted_at) {
            $daysSinceContact = $customer->last_contacted_at->diffInDays(now());
            if ($daysSinceContact < 1) {
                $breakdown['contacted_today'] = 20;
                $total += 20;
            } elseif ($daysSinceContact < 7) {
                $breakdown['contacted_this_week'] = 10;
                $total += 10;
            } elseif ($daysSinceContact < 30) {
                $breakdown['contacted_this_month'] = 5;
                $total += 5;
            }
        }

        // 5. Notes / tagged signals
        if ($customer->notes) {
            $breakdown['has_notes'] = 2;
            $total += 2;
        }

        // 6. Appointment signals
        $appointmentCount = $customer->appointments()->count();
        if ($appointmentCount > 0) {
            $score = min(15, $appointmentCount * 5);
            $breakdown['appointments'] = $score;
            $total += $score;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Update the customer's lead score in the database.
     */
    public function updateScore(Customer $customer): void
    {
        $result = $this->calculate($customer);

        $customer->update([
            'lead_score' => $result['total'],
        ]);

        // If score is above 50, auto-advance lead stage
        if ($result['total'] >= 80 && $customer->lead_stage === 'new') {
            $customer->update(['lead_stage' => 'qualified']);
        }
    }

    /**
     * Get a human-readable explanation of the lead score.
     */
    public function explain(Customer $customer): string
    {
        $result = $this->calculate($customer);
        $explanations = [];

        foreach ($result['breakdown'] as $reason => $points) {
            $explanations[] = "+{$points} {$reason}";
        }

        return "Lead score: {$result['total']}\n" . implode("\n", $explanations);
    }
}