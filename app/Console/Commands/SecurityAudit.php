<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SecurityAudit extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:audit {--fix : Automatically fix security issues}';

    /**
     * The console command description.
     */
    protected $description = 'Audit the application for common security issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running security audit...');

        $issues = [];
        $fixed = [];

        // Check for debug files
        $debugFiles = [
            public_path('info.php'),
            public_path('phpinfo.php'),
            public_path('test.php'),
            public_path('debug.php'),
        ];

        foreach ($debugFiles as $file) {
            if (File::exists($file)) {
                $issues[] = "Debug file found: " . basename($file);

                if ($this->option('fix')) {
                    File::delete($file);
                    $fixed[] = "Removed debug file: " . basename($file);
                    Log::warning('Security audit: Removed debug file', ['file' => $file]);
                }
            }
        }

        // Check upload directory permissions (skip on Windows as it uses different system)
        $uploadDirs = [
            public_path('uploads'),
            public_path('Transfer Files'),
            storage_path('app/public'),
        ];

        foreach ($uploadDirs as $dir) {
            if (File::exists($dir)) {
                // Only check Unix-style permissions on non-Windows systems
                if (PHP_OS_FAMILY !== 'Windows') {
                    $perms = substr(sprintf('%o', fileperms($dir)), -4);
                    if ($perms !== '0755') {
                        $issues[] = "Upload directory has insecure permissions: $dir ($perms)";

                        if ($this->option('fix')) {
                            chmod($dir, 0755);
                            $fixed[] = "Fixed permissions for: $dir";
                        }
                    }
                }

                // Check for .htaccess protection
                $htaccess = $dir . '/.htaccess';
                if (!File::exists($htaccess)) {
                    $issues[] = "Missing .htaccess protection: $dir";

                    if ($this->option('fix')) {
                        $htaccessContent = "# Deny access to PHP files\n<Files \"*.php\">\nOrder Allow,Deny\nDeny from all\n</Files>\n\n# Disable script execution\nAddHandler cgi-script .php .phtml .php3 .php4 .php5 .phar\nOptions -ExecCGI";
                        File::put($htaccess, $htaccessContent);
                        $fixed[] = "Added .htaccess protection to: $dir";
                    }
                }
            }
        }

        // Check environment configuration
        if (config('app.debug') && app()->environment('production')) {
            $issues[] = "Debug mode is enabled in production";
        }

        if (!config('app.key')) {
            $issues[] = "Application key is not set";
        }

        // Check rate limiting configuration
        $rateLimitConfig = config('security.rate_limiting');
        if (!$rateLimitConfig || $rateLimitConfig['login_attempts'] > 10) {
            $issues[] = "Rate limiting may be too permissive for login attempts";
        }

        // Display results
        if (empty($issues)) {
            $this->info('✅ No security issues found!');
        } else {
            $this->error('🚨 Security issues found:');
            foreach ($issues as $issue) {
                $this->line("  - $issue");
            }
        }

        if (!empty($fixed)) {
            $this->info('🔧 Fixed issues:');
            foreach ($fixed as $fix) {
                $this->line("  - $fix");
            }
        }

        return empty($issues) ? 0 : 1;
    }
}
