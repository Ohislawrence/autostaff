<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommerceGapToolsTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Commerce Org',
            'slug' => 'commerce-org',
            'currency' => 'NGN',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization_id', $this->organization->id);

        $this->customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'Commerce',
            'email' => 'commerce@example.com',
        ]);
        $this->customer->save();

        $this->product = new Product([
            'organization_id' => $this->organization->id,
            'name' => 'Rice 50kg',
            'price' => 50000,
            'currency' => 'NGN',
        ]);
        $this->product->save();
    }

    #[Test] public function add_to_cart_creates_persistent_cart()
    {
        $tool = new \App\Ai\Tools\BuiltIn\AddToCartTool();
        $result = $tool->execute([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, Cart::count());

        $cart = Cart::first();
        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(2, $cart->items()->first()->quantity);
        $this->assertSame(100000.0, $cart->subtotal());
    }

    #[Test] public function apply_discount_computes_percentage()
    {
        PromoCode::create([
            'organization_id' => $this->organization->id,
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        $tool = new \App\Ai\Tools\BuiltIn\ApplyDiscountTool();
        $result = $tool->execute(['code' => 'SAVE10', 'amount' => 1000]);

        $this->assertTrue($result['success']);
        $this->assertSame('900.00', $result['total']);
    }

    #[Test] public function create_ticket_persists_support_ticket()
    {
        $tool = new \App\Ai\Tools\BuiltIn\CreateTicketTool();
        $result = $tool->execute([
            'subject' => 'Delivery issue',
            'description' => 'Order not delivered',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, SupportTicket::count());
        $this->assertSame('Delivery issue', SupportTicket::first()->subject);
    }
}