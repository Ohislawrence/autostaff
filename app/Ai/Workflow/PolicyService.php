<?php

namespace App\Ai\Workflow;

use App\Models\AiEmployee;
use App\Models\Conversation;

/**
 * Business policy gate for tool execution.
 *
 * The backend owns permissions and constraints. This service enforces policy
 * (approval thresholds, discount caps, restricted tools) independent of the
 * LLM, so the AI can never silently bypass a business rule.
 */
class PolicyService
{
    /**
     * Tools that always require explicit human approval.
     */
    protected const APPROVAL_REQUIRED_TOOLS = [
        'record_payment',
        'cancel_order',
        'generate_invoice',
    ];

    /**
     * Evaluate whether a tool call is permitted and whether it needs approval.
     *
     * @return array{allowed: bool, requires_approval: bool, reason: ?string}
     */
    public function evaluate(
        AiEmployee $employee,
        Conversation $conversation,
        string $toolIdentifier,
        array $arguments,
    ): array {
        if (in_array($toolIdentifier, self::APPROVAL_REQUIRED_TOOLS, true)) {
            return [
                'allowed' => false,
                'requires_approval' => true,
                'reason' => "Tool '{$toolIdentifier}' requires human approval.",
            ];
        }

        // Discount caps on quotation generation.
        if ($toolIdentifier === 'generate_quotation') {
            $discount = (float) ($arguments['discount_percent'] ?? 0);
            $maxDiscount = $this->maxDiscountPercent($employee, $conversation);

            if ($discount > $maxDiscount) {
                return [
                    'allowed' => false,
                    'requires_approval' => true,
                    'reason' => "Discount of {$discount}% exceeds the {$maxDiscount}% limit and requires approval.",
                ];
            }
        }

        return ['allowed' => true, 'requires_approval' => false, 'reason' => null];
    }

    protected function maxDiscountPercent(AiEmployee $employee, Conversation $conversation): float
    {
        // Employee override, else org policy, else conservative default.
        $rules = $employee->escalation_rules ?? [];
        if (isset($rules['max_discount_percent']) && is_numeric($rules['max_discount_percent'])) {
            return (float) $rules['max_discount_percent'];
        }

        $orgPolicies = $employee->organization->policies ?? [];
        if (isset($orgPolicies['max_discount_percent']) && is_numeric($orgPolicies['max_discount_percent'])) {
            return (float) $orgPolicies['max_discount_percent'];
        }

        return 10.0;
    }
}