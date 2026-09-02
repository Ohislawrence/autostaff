<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Log;

class LanguageDetectionService
{
    /**
     * Simple keyword/pattern-based language detection for Nigerian languages.
     * Returns 'pidgin', 'yoruba', 'hausa', 'english', or 'unknown'.
     */
    public function detect(string $text): string
    {
        $text = strtolower(trim($text));

        // Strong Pidgin indicators (must match multiple to be confident)
        $pidginScore = 0;
        $pidginWords = ['dey', 'na', 'abeg', 'wahala', 'shey', 'ehn', 'sef',
            'wey', 'fit', 'go', 'don', 'comot', 'chop', 'oga',
            'no wahala', 'how far', 'i dey', 'shey you', 'nna',
            'oya', 'naija', 'wetin', 'e get'];
        foreach ($pidginWords as $word) {
            if (stripos($text, $word) !== false) $pidginScore++;
        }

        // Strong Yoruba indicators
        $yorubaScore = 0;
        $yorubaWords = ['bawo', 'mo n', 'wa', 'tí', 'ṣé',
            'orúkọ', 'kilode', 'ẹ se', 'mo fe', 'pẹlẹ',
            'ekaro', 'eku', 'olohun', 'o ti', 'n ko'];
        foreach ($yorubaWords as $word) {
            if (stripos($text, $word) !== false) $yorubaScore++;
        }

        // Strong Hausa indicators
        $hausaScore = 0;
        $hausaWords = ['sannu', 'nagode', 'yaya', 'ina', 'kwana',
            'lahiya', 'gaskiya', 'aboki', 'zafi', 'naira',
            'sai', 'wannan', 'me', 'ne', 'kuma'];
        foreach ($hausaWords as $word) {
            if (stripos($text, $word) !== false) $hausaScore++;
        }

        // Decision logic
        if ($pidginScore >= 2) return 'pidgin';
        if ($yorubaScore >= 2) return 'yoruba';
        if ($hausaScore >= 2) return 'hausa';

        return 'english'; // Default
    }

    /**
     * Get language-specific system instruction hints for the AI.
     */
    public function getLanguageHint(string $detectedLanguage): string
    {
        return match ($detectedLanguage) {
            'pidgin' => "You are speaking to a Nigerian customer who uses Pidgin English. Respond naturally in Nigerian Pidgin — don't sound robotic. Use casual, warm language.",
            'yoruba' => "The customer appears to be Yoruba-speaking. If they use Yoruba phrases, acknowledge them naturally. Respond primarily in English but you can include Yoruba greetings like 'Bawo ni', 'Ẹ se'.",
            'hausa' => "The customer appears to be Hausa-speaking. If they use Hausa phrases, acknowledge them naturally. Respond primarily in English but you can include Hausa greetings like 'Sannu', 'Nagode'.",
            default => "",
        };
    }

    /**
     * Check if text contains code-switched elements (mixing two languages).
     */
    public function hasCodeSwitching(string $text): bool
    {
        $pidginElements = $this->countElements($text, ['dey', 'na', 'abeg', 'wahala', 'shey', 'ehn', 'fit']);
        $yorubaElements = $this->countElements($text, ['bawo', 'ẹ se', 'kilode', 'mo fe']);
        $hausaElements = $this->countElements($text, ['sannu', 'nagode', 'yaya', 'ina']);
        $englishWords = str_word_count($text);

        return ($englishWords > 3) && (($pidginElements + $yorubaElements + $hausaElements) > 0);
    }

    protected function countElements(string $text, array $words): int
    {
        $count = 0;
        foreach ($words as $word) {
            if (stripos($text, $word) !== false) $count++;
        }
        return $count;
    }
}