<?php

namespace Nomdal;

class Config
{
    /**
     * Load the configuration array. Throws when config.php is missing.
     */
    public static function load(): array
    {
        $path = defined('NOMDAL_CONFIG_PATH')
            ? NOMDAL_CONFIG_PATH
            : dirname(__DIR__).'/config.php';

        if (! is_file($path)) {
            throw new \RuntimeException(
                'Missing config.php — copy config.example.php to config.php and fill in your values.'
            );
        }

        $config = require $path;

        if (! is_array($config)) {
            throw new \RuntimeException('config.php must return an array.');
        }

        return $config;
    }
}
