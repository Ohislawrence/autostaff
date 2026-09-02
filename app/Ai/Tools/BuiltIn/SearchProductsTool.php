<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Product;

class SearchProductsTool extends BaseTool
{
    protected string $identifier = 'search_products';
    protected string $name = 'Search Products';
    protected string $description = 'Search the product catalog by name, category, or keywords. Returns matching products.';
    protected string $category = 'products';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query to find products',
                ],
                'category' => [
                    'type' => 'string',
                    'description' => 'Optional category filter',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results',
                    'default' => 10,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'products' => ['type' => 'array'],
                'total' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $query = $parameters['query'] ?? '';
        $category = $parameters['category'] ?? null;
        $limit = $parameters['limit'] ?? 10;

        $orgId = app('current_organization_id');

        $products = Product::where('organization_id', $orgId)
            ->where('is_active', true)
            ->when($query, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            }))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->take($limit)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'name' => $p->name,
                'price' => (string) $p->price,
                'currency' => $p->currency,
                'category' => $p->category,
                'in_stock' => true, // will be checked by inventory tool
                'description' => $p->description ? substr($p->description, 0, 200) : null,
            ]);

        return $this->success("Found {$products->count()} products.", [
            'products' => $products->toArray(),
            'total' => $products->count(),
        ]);
    }
}