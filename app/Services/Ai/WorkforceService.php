<?php

namespace App\Services\Ai;

use App\Models\AiEmployee;
use App\Models\Organization;
use App\Models\Tool;
use Illuminate\Support\Str;

/**
 * Recommends and deploys an AI workforce (departments) for an organization.
 *
 * The template catalog lives in AiEmployeeController::getTemplates(); this
 * service receives the catalog as input so the single source of truth stays
 * in the controller.
 */
class WorkforceService
{
    public const DEPARTMENTS = [
        'revenue' => ['label' => 'Revenue', 'icon' => '💰'],
        'customer_experience' => ['label' => 'Customer Experience', 'icon' => '💬'],
        'operations' => ['label' => 'Operations', 'icon' => '🛒'],
        'administration' => ['label' => 'Administration', 'icon' => '🧑‍💼'],
    ];

    /** Always-included core roles (template ids). */
    protected const CORE = ['sales', 'support', 'receptionist'];

    /**
     * Recommend a workforce from the full template catalog, based on what the
     * organization sells and who it serves.
     *
     * @return array<int, array> template entries with `reason` + `recommended` flags
     */
    public function recommend(Organization $organization, array $templates): array
    {
        $signals = $this->signals($organization);

        $recommended = [
            'sales' => 'Closes sales, answers pricing questions, and creates orders.',
            'support' => 'Answers questions and resolves issues so nothing slips.',
            'receptionist' => 'Books appointments and fields enquiries.',
            'sales_development_rep' => 'Finds and qualifies outbound prospects, then follows up automatically.',
        ];

        if ($signals['commerce']) {
            $recommended['ecommerce'] = 'Handles product questions, carts, and store orders.';
        }
        if ($signals['bookings']) {
            $recommended['booking_agent'] = 'Manages reservations and booking requests.';
        }
        if ($signals['admin']) {
            $recommended['hr_assistant'] = 'Clears HR and admin questions, policies, and routine tasks.';
        }
        if ($signals['technical']) {
            $recommended['tech_support'] = 'Walks customers through technical issues.';
        }
        if ($signals['lead_heavy']) {
            $recommended['lead_qualifier'] = 'Scores and prioritizes inbound leads.';
        }

        $result = [];
        foreach ($templates as $template) {
            $id = $template['id'] ?? null;
            if (! $id || ! isset($recommended[$id])) {
                continue;
            }

            $template['reason'] = $recommended[$id];
            $template['recommended'] = ! in_array($id, self::CORE, true);
            $template['core'] = in_array($id, self::CORE, true);
            $template['department_icon'] = self::DEPARTMENTS[$template['department']]['icon'] ?? '';
            $template['department_label'] = self::DEPARTMENTS[$template['department']]['label'] ?? ucfirst($template['department']);
            $result[] = $template;
        }

        return $result;
    }

    /**
     * Create AI employees from a list of template entries.
     *
     * @return array{created: array, skipped: array}
     */
    public function deploy(Organization $organization, array $templates): array
    {
        $created = [];
        $skipped = [];
        $tracker = app(\App\Services\Billing\UsageTracker::class);

        foreach ($templates as $template) {
            if ($tracker->isAtLimit($organization, 'ai_employees')) {
                $skipped[] = $template['name'] ?? 'Employee';
                continue;
            }

            $employee = $this->createFromTemplate($organization, $template);
            $created[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->role,
            ];
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    protected function createFromTemplate(Organization $organization, array $template): AiEmployee
    {
        $employee = $organization->aiEmployees()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $template['name'] ?? 'AI Employee',
            'role' => $template['role'] ?? 'General',
            'department' => $template['department'] ?? null,
            'description' => $template['description'] ?? null,
            'avatar' => $this->departmentAvatar($template['department'] ?? null),
            'personality' => $template['personality'] ?? null,
            'tone' => $template['tone'] ?? 'professional',
            'language' => 'en',
            'system_instructions' => $template['instructions'] ?? null,
            'enabled_tools' => $template['tools'] ?? [],
            'is_active' => true,
        ]);

        $this->syncTools($employee, $template['tools'] ?? []);

        return $employee;
    }

    protected function syncTools(AiEmployee $employee, array $identifiers): void
    {
        if (empty($identifiers)) {
            return;
        }

        $toolIds = Tool::where('is_active', true)
            ->whereIn('identifier', $identifiers)
            ->pluck('id');

        $employee->tools()->sync(
            $toolIds->mapWithKeys(fn ($id) => [$id => [
                'is_allowed' => true,
                'requires_confirmation' => false,
            ]])->all()
        );
    }

    protected function departmentAvatar(?string $department): string
    {
        return self::DEPARTMENTS[$department]['icon'] ?? '🤖';
    }

    protected function signals(Organization $organization): array
    {
        $haystack = strtolower(implode(' ', array_filter([
            $organization->industry,
            $organization->description,
            $organization->policies['offering'] ?? null,
            $organization->policies['icp']['industry'] ?? null,
        ])));

        $contains = fn (array $words) => (bool) array_filter($words, fn ($w) => str_contains($haystack, $w));

        return [
            'commerce' => $contains(['retail', 'store', 'shop', 'product', 'ecommerce', 'e-commerce', 'fashion', 'furniture', 'electronics', 'food', 'grocery', 'order']),
            'bookings' => $contains(['clinic', 'wellness', 'salon', 'spa', 'health', 'dental', 'medical', 'fitness', 'booking', 'appointment', 'reservation', 'restaurant', 'real estate', 'law', 'consult', 'hotel']),
            'admin' => $contains(['hr', 'human resource', 'payroll', 'staff', 'admin', 'recruit', 'staffing']),
            'technical' => $contains(['saas', 'software', 'tech', 'it ', 'hosting', 'app', 'platform', 'digital']),
            'lead_heavy' => $contains(['lead', 'b2b', 'agency', 'consult', 'professional']),
        ];
    }
}
