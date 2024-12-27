<?php
/**
 * @package php-tmdb\laravel
 * @author Mark Redeman <markredeman@gmail.com>
 * @copyright (c) 2014, Mark Redeman
 */
return [
    /*
     * API key
     */
    'api_key' => env('TMDB_API_KEY', ''),

    /*
     * Bearer token (API Read Access Token)
     */
    'bearer_token' => env('TMDB_BEARER_TOKEN', ''),

    /**
     * Client options
     */
    'options' => [
        /**
         * Use https
         */
        'secure' => true,

        /*
         * Cache
         */
        'cache' => [
            'enabled' => true,
            // Keep the path empty or remove it entirely to default to storage/tmdb
            'path' => storage_path('tmdb')
        ],

        /*
         * Log
         */
        'log' => [
            'enabled' => true,
            // Keep the path empty or remove it entirely to default to storage/logs/tmdb.log
            'path' => storage_path('logs/tmdb.log')
        ],

        'http' => [
            'client' => null,
            'request_factory' => null,
            'response_factory' => null,
            'stream_factory' => null,
            'uri_factory' => null,
        ]
    ],
];
