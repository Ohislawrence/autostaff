<?php

namespace Tests\Feature;

use App\Ai\Tools\BuiltIn\RescheduleAppointmentTool;
use App\Ai\Tools\BuiltIn\ScheduleAppointmentTool;
use App\Models\AiEmployee;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Organization;
use App\Services\Appointments\AppointmentSlotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Clinic',
            'slug' => 'clinic',
            'onboarding_completed' => true,
            'timezone' => 'Africa/Lagos',
        ]);

        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);

        $this->customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
    }

    protected function createAvailability(string $day, string $start, string $end, int $duration = 30): Availability
    {
        return Availability::create([
            'organization_id' => $this->organization->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'slot_duration_minutes' => $duration,
            'is_active' => true,
        ]);
    }

    protected function monday(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY);
    }

    #[Test]
    public function it_returns_available_slots_and_excludes_booked()
    {
        $this->createAvailability('monday', '09:00', '10:00', 30);

        $date = $this->monday();
        $slots = app(AppointmentSlotService::class)->getAvailableSlots($this->organization, $date);
        $this->assertCount(2, $slots);

        Appointment::create([
            'organization_id' => $this->organization->id,
            'customer_id' => $this->customer->id,
            'title' => 'Taken',
            'start_time' => $date->copy()->setTime(9, 0),
            'end_time' => $date->copy()->setTime(9, 30),
            'status' => 'scheduled',
        ]);

        $slots = app(AppointmentSlotService::class)->getAvailableSlots($this->organization, $date);
        $this->assertCount(1, $slots);
        $this->assertSame('09:30', $slots[0]['start']);
    }

    #[Test]
    public function it_rejects_booking_outside_business_hours()
    {
        $this->createAvailability('monday', '09:00', '17:00', 30);

        $date = $this->monday();
        $result = app(ScheduleAppointmentTool::class)->execute([
            'customer_id' => $this->customer->id,
            'title' => 'Late',
            'start_time' => $date->copy()->setTime(18, 0)->toIso8601String(),
            'end_time' => $date->copy()->setTime(18, 30)->toIso8601String(),
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('outside business hours', $result['error']);
    }

    #[Test]
    public function it_rejects_double_booking()
    {
        $this->createAvailability('monday', '09:00', '17:00', 30);

        $date = $this->monday();
        $start = $date->copy()->setTime(9, 0);
        $end = $date->copy()->setTime(9, 30);

        Appointment::create([
            'organization_id' => $this->organization->id,
            'customer_id' => $this->customer->id,
            'title' => 'Existing',
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'scheduled',
        ]);

        $result = app(ScheduleAppointmentTool::class)->execute([
            'customer_id' => $this->customer->id,
            'title' => 'Clash',
            'start_time' => $start->toIso8601String(),
            'end_time' => $end->toIso8601String(),
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already booked', $result['error']);
    }

    #[Test]
    public function it_schedules_an_appointment_with_lifecycle_linkage()
    {
        $this->createAvailability('monday', '09:00', '17:00', 30);

        $employee = AiEmployee::create(['name' => 'Receptionist', 'role' => 'Receptionist', 'is_active' => true]);
        $conversation = Conversation::create([
            'ai_employee_id' => $employee->id,
            'customer_id' => $this->customer->id,
            'channel' => 'web_chat',
            'status' => 'open',
        ]);

        $date = $this->monday();
        $result = app(ScheduleAppointmentTool::class)->execute([
            'customer_id' => $this->customer->id,
            'title' => 'Consultation',
            'start_time' => $date->copy()->setTime(10, 0)->toIso8601String(),
            'end_time' => $date->copy()->setTime(10, 30)->toIso8601String(),
            '_employee' => $employee->id,
            '_conversation' => $conversation->id,
        ]);

        $this->assertTrue($result['success'], json_encode($result));

        $appointment = Appointment::first();
        $this->assertSame($this->customer->id, $appointment->customer_id);
        $this->assertSame($employee->id, $appointment->ai_employee_id);
        $this->assertSame($conversation->id, $appointment->conversation_id);
        $this->assertSame('Africa/Lagos', $appointment->timezone);
    }

    #[Test]
    public function it_rejects_reschedule_to_a_conflicting_slot()
    {
        $this->createAvailability('monday', '09:00', '17:00', 30);
        $date = $this->monday();

        $first = Appointment::create([
            'organization_id' => $this->organization->id,
            'customer_id' => $this->customer->id,
            'title' => 'First',
            'start_time' => $date->copy()->setTime(9, 0),
            'end_time' => $date->copy()->setTime(9, 30),
            'status' => 'scheduled',
        ]);

        Appointment::create([
            'organization_id' => $this->organization->id,
            'customer_id' => $this->customer->id,
            'title' => 'Second',
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(10, 30),
            'status' => 'scheduled',
        ]);

        $result = app(RescheduleAppointmentTool::class)->execute([
            'appointment_id' => $first->id,
            'new_start_time' => $date->copy()->setTime(10, 0)->toIso8601String(),
            'new_end_time' => $date->copy()->setTime(10, 30)->toIso8601String(),
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already booked', $result['error']);
    }
}
