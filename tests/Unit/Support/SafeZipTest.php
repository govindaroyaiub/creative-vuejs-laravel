<?php

use App\Support\SafeZip;

/** Build a zip on disk from an [entryName => contents] map. */
function makeZip(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'safezip') . '.zip';
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $contents) {
        if ($contents === null) {
            $zip->addEmptyDir(rtrim($name, '/'));
        } else {
            $zip->addFromString($name, $contents);
        }
    }
    $zip->close();

    return $path;
}

function destDir(): string
{
    $dir = sys_get_temp_dir() . '/safezip-dest-' . bin2hex(random_bytes(6));
    return $dir;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir() . '/safezip*') as $leftover) {
        is_dir($leftover) ? exec('rm -rf ' . escapeshellarg($leftover)) : @unlink($leftover);
    }
});

test('mac and windows metadata never reaches the extracted folder', function () {
    $zip = makeZip([
        'index.html' => '<html></html>',
        'img/logo.png' => 'png',
        '__MACOSX/' => null,
        '__MACOSX/._index.html' => 'resource fork',
        '.DS_Store' => 'junk',
        'img/.DS_Store' => 'junk',
        'img/._logo.png' => 'resource fork',
        'Thumbs.db' => 'junk',
        'desktop.ini' => 'junk',
    ]);
    $dest = destDir();

    SafeZip::extract($zip, $dest);

    expect(file_exists($dest . '/index.html'))->toBeTrue();
    expect(file_exists($dest . '/img/logo.png'))->toBeTrue();

    expect(is_dir($dest . '/__MACOSX'))->toBeFalse();
    expect(file_exists($dest . '/.DS_Store'))->toBeFalse();
    expect(file_exists($dest . '/img/.DS_Store'))->toBeFalse();
    expect(file_exists($dest . '/img/._logo.png'))->toBeFalse();
    expect(file_exists($dest . '/Thumbs.db'))->toBeFalse();
    expect(file_exists($dest . '/desktop.ini'))->toBeFalse();
});

test('a real banner still extracts untouched', function () {
    $zip = makeZip([
        'index.html' => '<html></html>',
        'assets/main.js' => 'console.log(1)',
        'assets/bg.jpg' => 'jpg',
    ]);
    $dest = destDir();

    SafeZip::extract($zip, $dest);

    expect(file_get_contents($dest . '/index.html'))->toBe('<html></html>');
    expect(file_get_contents($dest . '/assets/main.js'))->toBe('console.log(1)');
    expect(file_get_contents($dest . '/assets/bg.jpg'))->toBe('jpg');
});

test('an archive of nothing but metadata is rejected', function () {
    $zip = makeZip([
        '__MACOSX/._index.html' => 'resource fork',
        '.DS_Store' => 'junk',
    ]);

    expect(fn () => SafeZip::extract($zip, destDir()))
        ->toThrow(RuntimeException::class, 'no usable files');
});

test('zip slip is still refused', function () {
    $zip = makeZip(['../../public/shell.php' => '<?php']);

    expect(fn () => SafeZip::extract($zip, destDir()))
        ->toThrow(RuntimeException::class, 'unsafe entry');
});
