<?php

namespace Nomdal\Actions;

use Nomdal\ApiClient;

class Woo
{
    public static function init(): void
    {
        add_action('woocommerce_checkout_order_processed', [self::class, 'handleOrder']);
        add_action('woocommerce_new_order', [self::class, 'handleOrder']);
    }

    public static function handleOrder($orderId): void
    {
        if (! function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($orderId);
        if (! $order) {
            return;
        }

        $client = ApiClient::fromSettings();
        if (! $client) {
            return;
        }

        $customerId = self::syncCustomer($client, $order);

        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            $items[] = [
                'product_name' => $item->get_name(),
                'sku' => $product ? $product->get_sku() : '',
                'quantity' => $item->get_quantity(),
                'unit_price' => (float) wc_format_decimal($order->get_item_subtotal($item, false)),
                'total_price' => (float) wc_format_decimal($item->get_total()),
            ];
        }

        $client->createOrder([
            'customer_id' => $customerId,
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'subtotal' => (float) wc_format_decimal($order->get_subtotal()),
            'shipping' => (float) wc_format_decimal($order->get_shipping_total()),
            'tax' => (float) wc_format_decimal($order->get_total_tax()),
            'total' => (float) wc_format_decimal($order->get_total()),
            'payment_status' => $order->is_paid() ? 'paid' : 'unpaid',
            'payment_method' => $order->get_payment_method_title(),
            'metadata' => [
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key(),
            ],
            'items' => $items,
        ]);
    }

    protected static function syncCustomer(ApiClient $client, $order): ?int
    {
        $email = $order->get_billing_email();
        if (empty($email)) {
            return null;
        }

        $existing = $client->findCustomerByEmail($email);

        if ($existing->success && ! empty($existing->data['data'][0]['id'])) {
            return (int) $existing->data['data'][0]['id'];
        }

        $created = $client->createCustomer([
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'email' => $email,
            'phone' => $order->get_billing_phone(),
            'source' => 'woocommerce',
        ]);

        if ($created->success && ! empty($created->data['id'])) {
            return (int) $created->data['id'];
        }

        return null;
    }
}
