<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security settings for file uploads
    |
    */

    'file_upload' => [
        'max_file_size' => 20971520, // 20MB in bytes
        'max_total_size' => 52428800, // 50MB in bytes
        'max_files_per_upload' => 5,

        'allowed_extensions' => [
            'zip',
            'rar',
            '7z',
            'tar',
            'gz',
            'bz2',
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'svg',
            'mp4',
            'avi',
            'mov',
            'webm',
            'mp3',
            'wav'
        ],

        'allowed_mime_types' => [
            // Archives
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
            'application/x-bzip2',

            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',

            // Videos
            'video/mp4',
            'video/x-msvideo',
            'video/quicktime',
            'video/webm',

            // Audio
            'audio/mpeg',
            'audio/wav'
        ],

        'quarantine_suspicious_files' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Settings
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various endpoints
    |
    */

    'rate_limiting' => [
        'login_attempts' => 5,
        'login_window_minutes' => 1,
        'registration_attempts' => 5,
        'registration_window_minutes' => 60,
        'password_reset_attempts' => 3,
        'password_reset_window_minutes' => 60,
        'file_upload_attempts' => 10,
        'file_upload_window_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security headers to be sent with responses
    |
    */

    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'",
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Security
    |--------------------------------------------------------------------------
    |
    | Configure secure file storage settings
    |
    */

    'storage' => [
        'upload_directory_permissions' => 0755,
        'uploaded_file_permissions' => 0644,
        'create_htaccess_protection' => true,
        'disable_script_execution' => true,
        'enable_directory_indexing' => false,
    ],
];
