<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Helper for extracting user-supplied ZIPs without ZIP-slip exposure.
 *
 * `ZipArchive::extractTo()` honours the entry name verbatim, so an
 * archive containing `../../public/shell.php` will write that file
 * outside the intended directory. We open the archive, walk its
 * entry table once to assert each name is normalised + stays under
 * the destination, and only then call `extractTo`.
 */
class SafeZip
{
    /**
     * Extract $zipPath into $destination. Throws RuntimeException on
     * any path that would escape $destination, on a corrupt archive,
     * or on absolute / Windows-drive entry names.
     */
    public static function extract(string $zipPath, string $destination): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open uploaded archive.');
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false) {
                    throw new RuntimeException('Unreadable entry in uploaded archive.');
                }

                if (! self::isSafeEntry($name)) {
                    throw new RuntimeException(
                        'Refusing to extract archive: contains unsafe entry "' . $name . '".'
                    );
                }
            }

            if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
                throw new RuntimeException('Could not create extraction directory.');
            }

            if ($zip->extractTo($destination) !== true) {
                throw new RuntimeException('ZIP extraction failed.');
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Reject names that try to escape the destination via ".." or via
     * absolute paths (POSIX `/...` or Windows `C:\...`). Backslashes
     * are also rejected because PHP on Windows treats them as path
     * separators and some unzip libraries on Linux follow suit.
     */
    private static function isSafeEntry(string $name): bool
    {
        if ($name === '' || $name === '.' || $name === '..') {
            return false;
        }
        if (str_contains($name, "\0")) {
            return false;
        }
        if (str_starts_with($name, '/') || str_starts_with($name, '\\')) {
            return false;
        }
        if (preg_match('#^[A-Za-z]:[\\\\/]#', $name) === 1) {
            return false;
        }
        // Walk segments and forbid any `..` component.
        foreach (preg_split('#[\\\\/]+#', $name) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }
        return true;
    }
}
