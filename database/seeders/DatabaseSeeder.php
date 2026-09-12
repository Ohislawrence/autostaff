<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ToolsTableSeeder::class);
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(BuyerPersonaTemplateSeeder::class);

        // Create Platform Owner (super admin — no organization needed)
        $platformOwner = User::create([
            'name' => 'Platform Owner',
            'email' => 'platform@nomdal.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $platformOwner->assignRole('Platform Owner');

        // Create demo organization
        $org = Organization::create([
            'name' => 'Acme Furniture',
            'slug' => 'acme-furniture',
            'description' => 'Premium furniture store in Lagos, Nigeria.',
            'industry' => 'E-commerce',
            'website' => 'https://acmefurniture.example.com',
            'email' => 'hello@acmefurniture.example.com',
            'phone' => '+2348000000000',
            'timezone' => 'Africa/Lagos',
            'currency' => 'NGN',
            'onboarding_completed' => true,
            'is_active' => true,
        ]);

        // Create Tenant Owner
        $owner = User::create([
            'name' => 'Demo Business Owner',
            'email' => 'owner@nomdal.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Tenant Owner');
        $org->users()->attach($owner, ['role' => 'Owner', 'is_owner' => true]);

        // Create Tenant Admin
        $admin = User::create([
            'name' => 'Demo Admin',
            'email' => 'admin@nomdal.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Tenant Admin');
        $org->users()->attach($admin, ['role' => 'Admin']);

        // Create Manager
        $manager = User::create([
            'name' => 'Demo Manager',
            'email' => 'manager@nomdal.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Manager');
        $org->users()->attach($manager, ['role' => 'Manager']);

        // Create Agent
        $agent = User::create([
            'name' => 'Demo Agent',
            'email' => 'agent@nomdal.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $agent->assignRole('Agent');
        $org->users()->attach($agent, ['role' => 'Agent']);

        // Create Viewer
        $viewer = User::create([
            'name' => 'Demo Viewer',
            'email' => 'viewer@nomdal.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $viewer->assignRole('Viewer');
        $org->users()->attach($viewer, ['role' => 'Viewer']);
    }
}