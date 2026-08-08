<?php

use App\Models\Template;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('two templates with the same filename do not overwrite each other', function () {
    Storage::fake('public');
    $user = User::factory()->create(['permissions' => ['*']]);

    foreach (['first', 'second'] as $body) {
        $this->actingAs($user)->post(route('templates.store'), [
            'name' => "Report ({$body})",
            'file' => UploadedFile::fake()->createWithContent('report.zip', $body),
        ]);
    }

    $paths = Template::orderBy('id')->pluck('file_path');

    expect($paths)->toHaveCount(2);
    expect($paths[0])->not->toBe($paths[1]);

    // Both files must still be on disk with their own contents — before the
    // fix the second upload replaced the first at `templates/report.zip`.
    expect(Storage::disk('public')->get($paths[0]))->toBe('first');
    expect(Storage::disk('public')->get($paths[1]))->toBe('second');
});

test('the original filename is preserved for display and download', function () {
    Storage::fake('public');
    $user = User::factory()->create(['permissions' => ['*']]);

    $this->actingAs($user)->post(route('templates.store'), [
        'file' => UploadedFile::fake()->createWithContent('Q4 Launch Report.zip', 'x'),
    ]);

    $template = Template::sole();

    expect($template->file_name)->toBe('Q4 Launch Report.zip');
    expect($template->file_path)->toStartWith('templates/q4-launch-report_');
    expect($template->file_path)->toEndWith('.zip');
});

test('replacing a template file removes the old one', function () {
    Storage::fake('public');
    $user = User::factory()->create(['permissions' => ['*']]);

    $this->actingAs($user)->post(route('templates.store'), [
        'file' => UploadedFile::fake()->createWithContent('report.zip', 'old'),
    ]);

    $template = Template::sole();
    $oldPath = $template->file_path;

    $this->actingAs($user)->post(route('templates.update', $template), [
        'file' => UploadedFile::fake()->createWithContent('report.zip', 'new'),
    ]);

    $newPath = $template->fresh()->file_path;

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    expect(Storage::disk('public')->get($newPath))->toBe('new');
});
