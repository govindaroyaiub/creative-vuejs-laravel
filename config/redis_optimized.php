<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Redis configuration for production caching and session management
    |
    */

    'cache' => [
        'enabled' => env('REDIS_CACHE_ENABLED', false),
        'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
        'prefix' => env('REDIS_CACHE_PREFIX', 'creative_app_cache'),
        'ttl' => [
            'default' => env('REDIS_CACHE_TTL', 3600), // 1 hour
            'short' => 300, // 5 minutes
            'medium' => 1800, // 30 minutes
            'long' => 7200, // 2 hours
            'daily' => 86400, // 24 hours
        ],
    ],

    'session' => [
        'enabled' => env('REDIS_SESSION_ENABLED', false),
        'connection' => env('REDIS_SESSION_CONNECTION', 'session'),
        'prefix' => env('REDIS_SESSION_PREFIX', 'creative_app_session'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'encrypt' => env('SESSION_ENCRYPT', false),
        'gc_probability' => 2,
        'gc_divisor' => 100,
    ],

    'queue' => [
        'enabled' => env('REDIS_QUEUE_ENABLED', false),
        'connection' => env('REDIS_QUEUE_CONNECTION', 'queue'),
        'prefix' => env('REDIS_QUEUE_PREFIX', 'creative_app_queue'),
        'default_queue' => env('REDIS_DEFAULT_QUEUE', 'default'),
        'retry_after' => env('REDIS_QUEUE_RETRY_AFTER', 90),
        'block_for' => null,
    ],

    'connections' => [
        'cache' => [
            'host' => env('REDIS_CACHE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_CACHE_PASSWORD', env('REDIS_PASSWORD')),
            'port' => env('REDIS_CACHE_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_CACHE_DB', 1),
            'read_write_timeout' => 60,
            'options' => [
                'prefix' => env('REDIS_CACHE_PREFIX', 'creative_app_cache:'),
            ],
        ],

        'session' => [
            'host' => env('REDIS_SESSION_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_SESSION_PASSWORD', env('REDIS_PASSWORD')),
            'port' => env('REDIS_SESSION_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_SESSION_DB', 2),
            'read_write_timeout' => 60,
            'options' => [
                'prefix' => env('REDIS_SESSION_PREFIX', 'creative_app_session:'),
            ],
        ],

        'queue' => [
            'host' => env('REDIS_QUEUE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_QUEUE_PASSWORD', env('REDIS_PASSWORD')),
            'port' => env('REDIS_QUEUE_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_QUEUE_DB', 3),
            'read_write_timeout' => 60,
            'options' => [
                'prefix' => env('REDIS_QUEUE_PREFIX', 'creative_app_queue:'),
            ],
        ],
    ],

    'performance' => [
        'serializer' => env('REDIS_SERIALIZER', 'php'), // php, igbinary, json
        'compression' => env('REDIS_COMPRESSION', false),
        'persistent' => env('REDIS_PERSISTENT', false),
        'read_timeout' => env('REDIS_READ_TIMEOUT', 60),
        'timeout' => env('REDIS_TIMEOUT', 5),
        'retry_interval' => env('REDIS_RETRY_INTERVAL', 100),
        'max_retries' => env('REDIS_MAX_RETRIES', 3),
    ],

    'monitoring' => [
        'enabled' => env('REDIS_MONITORING_ENABLED', false),
        'slow_log_threshold' => env('REDIS_SLOW_LOG_THRESHOLD', 100), // milliseconds
        'memory_usage_alert' => env('REDIS_MEMORY_ALERT', 80), // percentage
        'connection_pool_size' => env('REDIS_POOL_SIZE', 10),
    ],
];
