<?php

namespace Nomdal\Shopify;

use Nomdal\NomdalClient;

class Sync
{
    private $nomdal;

    public function __construct(NomdalClient $nomdal)
    {
        $this->nomdal = $nomdal;
    }

    /**
     * Sync a Shopify order into Nomdal: find-or-create the customer, then
     * create the order with its line items.
     */
    public function syncOrder(array $order): bool
    {
        if (empty($order['id'])) {
            return false;
        }

        $customerId = $this->syncCustomer($order);

        $items = [];
        foreach ($order['line_items'] ?? [] as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);

            $items[] = [
                'product_name' => $item['title'] ?? $item['name'] ?? '',
                'sku' => $item['sku'] ?? '',
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_price' => $price * $quantity,
            ];
        }

        $shipping = (float) ($order['total_shipping_price_set']['shop_money']['amount'] ?? 0);

        $response = $this->nomdal->createOrder([
            'customer_id' => $customerId,
            'status' => $order['financial_status'] ?? 'paid',
            'currency' => $order['currency'] ?? 'USD',
            'subtotal' => (float) ($order['subtotal_price'] ?? 0),
            'shipping' => $shipping,
            'tax' => (float) ($order['total_tax'] ?? 0),
            'total' => (float) ($order['total_price'] ?? 0),
            'payment_status' => ($order['financial_status'] ?? '') === 'paid' ? 'paid' : 'unpaid',
            'payment_method' => $order['payment_gateway_names'][0] ?? '',
            'metadata' => [
                'order_id' => (string) $order['id'],
                'order_number' => (string) ($order['order_number'] ?? ''),
            ],
            'items' => $items,
        ]);

        return $response->success;
    }

    /**
     * Find-or-create a Nomdal customer from a Shopify order (or a
     * `customers/create` webhook payload wrapped as ['customer' => ..., 'email' => ...]).
     */
    public function syncCustomer(array $order): ?int
    {
        $customer = $order['customer'] ?? [];
        $email = $order['email'] ?? $customer['email'] ?? '';

        if ($email === '') {
            return null;
        }

        $existing = $this->nomdal->findCustomerByEmail($email);
        if ($existing->success && ! empty($existing->data['data'][0]['id'])) {
            return (int) $existing->data['data'][0]['id'];
        }

        $created = $this->nomdal->createCustomer([
            'first_name' => $customer['first_name'] ?? '',
            'last_name' => $customer['last_name'] ?? '',
            'email' => $email,
            'phone' => $customer['phone'] ?? $order['phone'] ?? '',
            'source' => 'shopify',
        ]);

        if ($created->success && ! empty($created->data['id'])) {
            return (int) $created->data['id'];
        }

        return null;
    }
}
