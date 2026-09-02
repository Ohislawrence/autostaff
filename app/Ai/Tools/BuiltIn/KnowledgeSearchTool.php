<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Services\Knowledge\KnowledgeRagService;

class KnowledgeSearchTool extends BaseTool
{
    protected string $category = 'knowledge';

    public function getIdentifier(): string { return 'knowledge_search'; }
    public function getName(): string { return 'Search Knowledge Base'; }
    public function getDescription(): string { return 'Search the knowledge base for relevant information to answer customer questions.'; }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Search query'],
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
                'results_found' => ['type' => 'integer'],
                'knowledge' => ['type' => 'array'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        try {
            $ragService = app(KnowledgeRagService::class);
            
            // Get current employee's knowledge base IDs from context if available
            $employee = $parameters['_employee'] ?? null;
            $knowledgeBaseIds = $employee?->business_knowledge_ids;
            
            $results = $ragService->search($orgId, $parameters['query'], 3, $knowledgeBaseIds);

            $chunks = [];
            foreach (($results['chunks'] ?? []) as $chunk) {
                $chunks[] = [
                    'content' => $chunk['content'] ?? '',
                    'source' => $chunk['source_title'] ?? 'Unknown',
                ];
            }

            return [
                'success' => true,
                'query' => $parameters['query'],
                'results_found' => count($chunks),
                'knowledge' => $chunks,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Knowledge search failed.',
                'results_found' => 0,
                'knowledge' => [],
            ];
        }
    }
}