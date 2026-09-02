<?php

namespace App\Services\Knowledge;

use App\Models\KnowledgeSource;
use App\Services\Tenant\TenantStorageManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;

class DocumentProcessor
{
    public function __construct(
        protected TenantStorageManager $storage,
    ) {}

    /**
     * Extract text from a knowledge source.
     */
    public function extractText(KnowledgeSource $source): string
    {
        return match ($source->type) {
            'pdf' => $this->extractFromPdf($source->file_path),
            'docx' => $this->extractFromDocx($source->file_path),
            'txt' => $this->extractTxtFile($source->file_path),
            'csv' => $this->extractFromCsv($source->file_path),
            'url' => $this->fetchFromUrl($source->source_url),
            'manual', 'faq' => $source->content ?? '',
            default => $source->content ?? '',
        };
    }

    protected function extractFromPdf(?string $path): string
    {
        if (empty($path)) return '';

        try {
            $fullPath = $this->storage->fullPath($path);
            if (! file_exists($fullPath)) {
                Log::warning('PDF file not found', ['path' => $path, 'fullPath' => $fullPath]);
                return '';
            }

            $parser = new PdfParser();
            $pdf = $parser->parseFile($fullPath);
            return mb_substr($pdf->getText(), 0, 100000);
        } catch (\Exception $e) {
            Log::error('PDF extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    protected function extractFromDocx(?string $path): string
    {
        if (empty($path)) return '';

        try {
            $fullPath = $this->storage->fullPath($path);
            if (! file_exists($fullPath)) {
                Log::warning('DOCX file not found', ['path' => $path, 'fullPath' => $fullPath]);
                return '';
            }

            $phpWord = PhpWordIOFactory::load($fullPath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $text .= $child->getText() . "\n";
                            }
                        }
                    }
                }
            }

            return mb_substr($text, 0, 100000);
        } catch (\Exception $e) {
            Log::error('DOCX extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    protected function extractFromCsv(?string $path): string
    {
        if (empty($path)) return '';

        try {
            $fullPath = $this->storage->fullPath($path);
            if (! file_exists($fullPath)) {
                Log::warning('CSV file not found', ['path' => $path, 'fullPath' => $fullPath]);
                return '';
            }

            $rows = array_map('str_getcsv', file($fullPath));
            $headers = array_shift($rows);
            $text = "CSV Data:\n";
            $text .= "Columns: " . implode(', ', $headers) . "\n";

            foreach (array_slice($rows, 0, 500) as $row) {
                $line = [];
                foreach ($headers as $i => $header) {
                    $line[] = "{$header}: " . ($row[$i] ?? '');
                }
                $text .= implode(' | ', $line) . "\n";
            }

            return mb_substr($text, 0, 50000);
        } catch (\Exception $e) {
            Log::error('CSV extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Fetch content from a URL.
     */
    protected function fetchFromUrl(?string $url): string
    {
        if (empty($url)) return '';

        try {
            $response = Http::timeout(15)
                ->withUserAgent('AI-Employee-Platform/1.0')
                ->get($url);

            if ($response->successful()) {
                $body = $response->body();
                // Extract metadata (title, meta description) before stripping
                $title = '';
                $metaDesc = '';
                if (preg_match('#<title[^>]*>(.*?)</title>#si', $body, $m)) {
                    $title = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                if (preg_match('#<meta\s+name=["\']description["\'][^>]*content=["\']([^"\']+)["\'][^>]*>#si', $body, $m)) {
                    $metaDesc = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                // Strip non-content blocks
                $body = preg_replace('#<script[^>]*>.*?</script>#si', '', $body);
                $body = preg_replace('#<style[^>]*>.*?</style>#si', '', $body);
                $body = preg_replace('#<noscript[^>]*>.*?</noscript>#si', '', $body);
                $body = preg_replace('#<svg[^>]*>.*?</svg>#si', '', $body);
                $body = preg_replace('#<nav[^>]*>.*?</nav>#si', '', $body);
                $body = preg_replace('#<footer[^>]*>.*?</footer>#si', '', $body);
                $body = preg_replace('#<head[^>]*>.*?</head>#si', '', $body);
                // Extract text from remaining HTML
                $text = strip_tags($body);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                // Prepend metadata as useful context
                $parts = [];
                if (! empty($title)) $parts[] = "Page title: {$title}";
                if (! empty($metaDesc)) $parts[] = "Description: {$metaDesc}";
                if (! empty($text)) $parts[] = $text;
                $result = implode("\n\n", $parts);
                if (empty(trim($result))) {
                    Log::warning('URL returned no extractable text', ['url' => $url]);
                    return '';
                }
                // Limit to ~50KB
                return mb_substr($result, 0, 50000);
            }

            Log::warning('URL fetch failed', ['url' => $url, 'status' => $response->status()]);
            return '';
        } catch (\Exception $e) {
            Log::error('URL fetch error', ['url' => $url, 'error' => $e->getMessage()]);
            return '';
        }
    }

    protected function extractTxtFile(?string $path): string
    {
        if (empty($path)) return '';

        try {
            $fullPath = $this->storage->fullPath($path);
            if (! file_exists($fullPath)) {
                Log::warning('TXT file not found', ['path' => $path, 'fullPath' => $fullPath]);
                return '';
            }

            return mb_substr(file_get_contents($fullPath), 0, 50000);
        } catch (\Exception $e) {
            Log::error('TXT extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Clean extracted text for better processing.
     */
    public function cleanText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}