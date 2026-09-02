<?php

namespace App\Support;

class AiJson
{
    /**
     * Robustly extract a JSON object/array from an LLM response.
     * Handles markdown code fences, surrounding prose and trailing commas.
     */
    public static function parse(string $content): ?array
    {
        $content = trim($content);

        if ($content === '') {
            return null;
        }

        // Strip ```json ... ``` fences.
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $content, $m)) {
            $content = trim($m[1]);
        }

        // Grab the first balanced-ish JSON block.
        if (preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/', $content, $m)) {
            $content = trim($m[1]);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Second chance: strip trailing commas (common LLM mistake).
        $cleaned = preg_replace('/,\s*([}\]])/', '$1', $content);
        $decoded = json_decode($cleaned, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
