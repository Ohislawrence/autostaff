<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Services\Channels\ImageAnalysisService;
use Illuminate\Support\Facades\Log;

class ExtractDocumentDataTool extends BaseTool
{
    protected string $identifier = 'extract_document_data';
    protected string $name = 'Extract Document Data';
    protected string $description = 'Extract structured information from documents, screenshots, or images. Use this to process proof-of-payment screenshots, ID cards, invoices, lab requisition forms, or any customer-submitted document.';
    protected string $category = 'documents';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'media_url' => ['type' => 'string', 'description' => 'URL of the image or document to analyze'],
                'document_type' => ['type' => 'string', 'description' => 'Hint: payment_proof, invoice, id_card, product_photo, damaged_goods, other'],
                'extract_fields' => [
                    'type' => 'array',
                    'description' => 'Specific fields to extract (e.g., amount, reference_number, name, date)',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['media_url'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'document_type' => ['type' => 'string'],
                'extracted_data' => ['type' => 'object'],
                'raw_description' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $mediaUrl = $parameters['media_url'] ?? '';
        $documentType = $parameters['document_type'] ?? 'other';

        if (empty($mediaUrl)) {
            return $this->error('No media URL provided.');
        }

        try {
            $imageService = app(ImageAnalysisService::class);

            // Build a type-specific prompt
            $typePrompts = [
                'payment_proof' => 'Identify the amount paid, payment reference/transaction ID, bank name, date, and sender name.',
                'invoice' => 'Extract the invoice number, vendor name, total amount, due date, and line items.',
                'id_card' => 'Extract the name, ID number, date of birth, and issuing authority.',
                'product_photo' => 'Describe the product, identify brand/model if visible, note condition.',
                'damaged_goods' => 'Describe the damage visible, identify the product, note extent of damage.',
                'other' => 'Describe all visible text, numbers, dates, and key information.',
            ];

            $description = $typePrompts[$documentType] ?? $typePrompts['other'];

            // Use the image analysis service (handles WhatsApp media URL + access token)
            $accessToken = config('services.whatsapp.access_token') ?? '';
            $analysis = $imageService->analyze($mediaUrl, $accessToken);

            if (! $analysis) {
                return $this->error('Could not analyze the document. The image may be inaccessible or the service is not configured.');
            }

            // Build structured extracted data based on document type
            $extractedData = $this->parseExtractedFields($analysis, $documentType, $parameters['extract_fields'] ?? []);

            return $this->success("Document analyzed successfully. Type: {$documentType}", [
                'document_type' => $documentType,
                'raw_description' => $analysis,
                'extracted_data' => $extractedData,
            ]);

        } catch (\Exception $e) {
            Log::error('ExtractDocumentData error', ['error' => $e->getMessage()]);
            return $this->error('Failed to extract document data: ' . $e->getMessage());
        }
    }

    /**
     * Parse the raw AI description into structured fields.
     */
    protected function parseExtractedFields(string $description, string $documentType, array $requestedFields): array
    {
        $fields = [];

        // Common pattern-based extraction from AI descriptions
        // For payment proof: look for amount patterns, reference numbers
        if ($documentType === 'payment_proof' || in_array('amount', $requestedFields)) {
            if (preg_match('/amount[\s:]*[₦#]?([0-9,.]+)/i', $description, $m)) {
                $fields['amount'] = str_replace(',', '', $m[1]);
            }
            if (preg_match('/(?:reference|ref|transaction)[\s:]*([A-Z0-9\-_]+)/i', $description, $m)) {
                $fields['reference'] = $m[1];
            }
        }

        if (in_array('date', $requestedFields)) {
            if (preg_match('/(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})/', $description, $m)) {
                $fields['date'] = $m[1];
            }
        }

        if (in_array('name', $requestedFields)) {
            if (preg_match('/name[\s:]*([A-Za-z\s]+)/i', $description, $m)) {
                $fields['name'] = trim($m[1]);
            }
        }

        return $fields;
    }
}