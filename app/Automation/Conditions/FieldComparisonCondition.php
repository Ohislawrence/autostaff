<?php

namespace App\Automation\Conditions;

use App\Automation\Contracts\ConditionInterface;

class FieldComparisonCondition implements ConditionInterface
{
    public function identifier(): string
    {
        return 'field_comparison';
    }

    public function label(): string
    {
        return 'Field Comparison';
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'field' => [
                    'type' => 'string',
                    'description' => 'The data field to evaluate (dot notation supported).',
                ],
                'operator' => [
                    'type' => 'string',
                    'enum' => ['equals', 'not_equals', 'greater_than', 'less_than', 'contains', 'in', 'not_in', 'exists', 'not_exists'],
                ],
                'value' => [
                    'description' => 'The value to compare against.',
                ],
                'logic' => [
                    'type' => 'string',
                    'enum' => ['and', 'or'],
                    'default' => 'and',
                ],
            ],
            'required' => ['field', 'operator'],
        ];
    }

    public function evaluate(array $config, array $triggerData): bool
    {
        $field = $config['field'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $value = $config['value'] ?? null;

        $actualValue = data_get($triggerData, $field);

        return match ($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'greater_than' => (float) $actualValue > (float) $value,
            'less_than' => (float) $actualValue < (float) $value,
            'contains' => is_string($actualValue) && str_contains(strtolower($actualValue), strtolower((string) $value)),
            'in' => in_array($actualValue, (array) $value),
            'not_in' => ! in_array($actualValue, (array) $value),
            'exists' => ! is_null($actualValue),
            'not_exists' => is_null($actualValue),
            default => false,
        };
    }
}