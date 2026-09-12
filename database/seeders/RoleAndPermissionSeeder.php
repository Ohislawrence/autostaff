<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Platform (super admin)
            'platform.access',
            'platform.manage-tenants',
            'platform.manage-users',
            'platform.manage-plans',
            'platform.view-analytics',
            'platform.manage-settings',

            // Organization management
            'organization.manage',
            'organization.update',
            'organization.delete',
            'organization.manage-team',
            'organization.manage-roles',
            'organization.manage-billing',

            // AI Employees
            'ai-employees.view',
            'ai-employees.create',
            'ai-employees.update',
            'ai-employees.delete',
            'ai-employees.manage-tools',
            'ai-employees.manage-channels',
            'ai-employees.test',

            // Conversations
            'conversations.view',
            'conversations.send-message',
            'conversations.assign',
            'conversations.resolve',
            'conversations.delete',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'customers.export',

            // Leads
            'leads.view',
            'leads.create',
            'leads.update',
            'leads.delete',
            'leads.manage-pipeline',
            'leads.export',

            // CRM
            'crm.view',
            'crm.manage',

            // Prospecting (outbound AI SDR)
            'prospecting.view',
            'prospecting.manage',

            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.manage-inventory',

            // Orders
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.delete',
            'orders.manage-status',

            // Appointments
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',

            // Knowledge
            'knowledge.view',
            'knowledge.create',
            'knowledge.update',
            'knowledge.delete',
            'knowledge.process',

            // Automations
            'automations.view',
            'automations.create',
            'automations.update',
            'automations.delete',

            // Integrations
            'integrations.view',
            'integrations.manage',
            'integrations.connect',

            // Analytics
            'analytics.view',
            'analytics.export',

            // Settings
            'settings.view',
            'settings.update',

            // Billing
            'billing.view',
            'billing.manage',

            // API
            'api.access',
            'api.manage-keys',

            // Webhooks
            'webhooks.view',
            'webhooks.manage',

            // Audit
            'audit.view',

            // Tasks
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $platformOwner = Role::firstOrCreate(['name' => 'Platform Owner', 'guard_name' => 'web']);
        $platformOwner->givePermissionTo(Permission::all());

        $tenantOwner = Role::firstOrCreate(['name' => 'Tenant Owner', 'guard_name' => 'web']);
        $tenantOwner->givePermissionTo([
            'organization.manage', 'organization.update', 'organization.manage-team',
            'organization.manage-roles', 'organization.manage-billing',
            'ai-employees.view', 'ai-employees.create', 'ai-employees.update',
            'ai-employees.delete', 'ai-employees.manage-tools', 'ai-employees.manage-channels',
            'ai-employees.test',
            'conversations.view', 'conversations.send-message', 'conversations.assign',
            'conversations.resolve', 'conversations.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'customers.export',
            'leads.view', 'leads.create', 'leads.update', 'leads.delete', 'leads.manage-pipeline', 'leads.export',
            'crm.view', 'crm.manage',
            'prospecting.view', 'prospecting.manage',
            'products.view', 'products.create', 'products.update', 'products.delete', 'products.manage-inventory',
            'orders.view', 'orders.create', 'orders.update', 'orders.delete', 'orders.manage-status',
            'appointments.view', 'appointments.create', 'appointments.update', 'appointments.delete',
            'knowledge.view', 'knowledge.create', 'knowledge.update', 'knowledge.delete', 'knowledge.process',
            'automations.view', 'automations.create', 'automations.update', 'automations.delete',
            'integrations.view', 'integrations.manage', 'integrations.connect',
            'analytics.view', 'analytics.export',
            'settings.view', 'settings.update',
            'billing.view', 'billing.manage',
            'api.access', 'api.manage-keys',
            'webhooks.view', 'webhooks.manage',
            'audit.view',
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
        ]);

        $tenantAdmin = Role::firstOrCreate(['name' => 'Tenant Admin', 'guard_name' => 'web']);
        $tenantAdmin->givePermissionTo([
            'ai-employees.view', 'ai-employees.create', 'ai-employees.update', 'ai-employees.manage-tools',
            'ai-employees.manage-channels', 'ai-employees.test',
            'conversations.view', 'conversations.send-message', 'conversations.assign', 'conversations.resolve',
            'customers.view', 'customers.create', 'customers.update', 'customers.export',
            'leads.view', 'leads.create', 'leads.update', 'leads.manage-pipeline', 'leads.export',
            'crm.view', 'crm.manage',
            'prospecting.view', 'prospecting.manage',
            'products.view', 'products.create', 'products.update',
            'orders.view', 'orders.create', 'orders.update',
            'appointments.view', 'appointments.create', 'appointments.update',
            'knowledge.view', 'knowledge.create', 'knowledge.update', 'knowledge.delete',
            'automations.view', 'automations.create', 'automations.update',
            'integrations.view', 'integrations.manage',
            'analytics.view',
            'settings.view',
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
        ]);

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->givePermissionTo([
            'ai-employees.view',
            'conversations.view', 'conversations.send-message', 'conversations.assign', 'conversations.resolve',
            'customers.view', 'customers.create', 'customers.update',
            'leads.view', 'leads.create', 'leads.update', 'leads.manage-pipeline',
            'crm.view',
            'prospecting.view',
            'products.view',
            'orders.view', 'orders.update',
            'appointments.view', 'appointments.create', 'appointments.update',
            'analytics.view',
            'tasks.view', 'tasks.create', 'tasks.update',
        ]);

        $agent = Role::firstOrCreate(['name' => 'Agent', 'guard_name' => 'web']);
        $agent->givePermissionTo([
            'conversations.view', 'conversations.send-message',
            'customers.view',
            'leads.view',
            'products.view',
            'orders.view',
            'tasks.view', 'tasks.update',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo([
            'conversations.view',
            'customers.view',
            'leads.view',
            'analytics.view',
        ]);
    }
}