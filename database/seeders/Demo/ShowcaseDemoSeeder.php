<?php

namespace Database\Seeders\Demo;

use App\Models\Client;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newPreview;
use App\Models\newSocial;
use App\Models\newVersion;
use App\Models\newVideo;
use App\Models\User;
use App\Models\VideoSize;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMO ONLY: a preview with a video project and a social project, two rounds
 * each and every format in use, so the in-context showcases in
 * /previews/review/{slug} can be tried without real uploads.
 *
 * Social images are placeholder photos (picsum.photos) with ad-style text
 * drawn on, in public/uploads/socials/demo/ (git ignored).
 *
 * The clips are short public sample videos with sound (samplelib.com) in
 * public/uploads/videos/demo/ (git ignored). They are 16:9; the showcase
 * crops them to each size's frame.
 *
 *   php artisan db:seed --class="Database\Seeders\Demo\ShowcaseDemoSeeder"
 *
 * Re-running replaces the previous demo preview.
 */
class ShowcaseDemoSeeder extends Seeder
{
    private const NAME = 'Video showcase demo';

    private const DIR = 'uploads/videos/demo/';

    public function run(): void
    {
        // Keep the same link across re-runs.
        $slug = newPreview::where('name', self::NAME)->value('slug') ?? Str::uuid()->toString();
        newPreview::where('name', self::NAME)->get()->each->delete();

        $client = Client::where('name', 'Paradiso Films')->first() ?? Client::firstOrFail();
        $user = User::firstOrFail();

        $preview = newPreview::create([
            'slug' => $slug,
            'name' => self::NAME,
            'client_id' => $client->id,
            'header_logo_id' => $client->id,
            'team_members' => [$user->id],
            'uploader_id' => $user->id,
            'color_palette_id' => $client->color_palette_id,
            'requires_login' => false,
            'show_planetnine_logo' => true,
            'show_sidebar_logo' => true,
            'show_footer' => true,
            'show_tour_guide' => false,
        ]);

        $category = newCategory::create([
            'preview_id' => $preview->id, 'name' => 'Trailer cutdowns', 'type' => 'video', 'is_active' => true,
        ]);

        // Round 1 and Round 2 use different clips, so "Compare rounds" shows a change.
        $rounds = [
            ['Round 1', 'First cut.', false, ['clip_a.mp4', 'clip_b.mp4']],
            ['Round 2', 'Logo bigger, new end card.', true, ['clip_c.mp4', 'clip_b.mp4']],
        ];

        // Version => Set => sizes
        $layout = [
            'Social' => [
                'Stories & reels' => ['1080x1920', '720x1280'],
                'Feed' => ['1080x1080', '1080x1350'],
            ],
            'Online' => [
                'Pre-roll' => ['1920x1080', '1440x1080'],
                'In-page video' => ['336x280', '416x346', '328x574'],
            ],
        ];

        foreach ($rounds as [$roundName, $notes, $active, $clips]) {
            $round = newFeedback::create([
                'category_id' => $category->id, 'name' => $roundName, 'description' => $notes,
                'is_active' => $active, 'is_approved' => false,
            ]);

            $i = 0;
            foreach ($layout as $versionName => $sets) {
                $version = newFeedbackSet::create(['feedback_id' => $round->id, 'name' => $versionName]);

                foreach ($sets as $setName => $sizes) {
                    $set = newVersion::create(['feedback_set_id' => $version->id, 'name' => $setName]);

                    foreach ($sizes as $pos => $sizeName) {
                        $size = VideoSize::where('name', $sizeName)->firstOrFail();
                        $clip = $clips[$i++ % count($clips)];
                        $file = public_path(self::DIR.$clip);

                        newVideo::create([
                            'version_id' => $set->id,
                            'name' => "Trailer_{$sizeName}",
                            'path' => self::DIR.$clip,
                            'size_id' => $size->id,
                            'codec' => 'H.264',
                            'aspect_ratio' => $this->ratio($size->width, $size->height),
                            'fps' => '30',
                            'file_size' => is_file($file) ? round(filesize($file) / 1048576, 1).' MB' : null,
                            // Landscape / in-page videos get a companion banner.
                            'companion_banner_path' => $versionName === 'Online' ? self::DIR.'companion_300x250.svg' : null,
                            'position' => $pos + 1,
                        ]);
                    }
                }
            }
        }

        $this->seedSocials($preview);

        $this->command?->info("Demo preview: /previews/review/{$preview->slug}");
    }

    private function seedSocials(newPreview $preview): void
    {
        $category = newCategory::create([
            'preview_id' => $preview->id, 'name' => 'Campaign socials', 'type' => 'social', 'is_active' => false,
        ]);

        // Storyboard panels: chosen frame (never auto), names are the shot notes.
        $board = [
            'WIDE · city at dawn', 'MED · she checks her phone', 'CU · the product in hand',
            'POV · walking into the store', 'MED · friends react', 'END CARD · logo + CTA',
        ];

        // Version => Set => image files (round prefix added below)
        $layout = [
            'Instagram & Facebook' => [
                'Carousel' => ['feed_1', 'feed_2', 'feed_3'],
                'Portrait post' => ['portrait'],
                'Stories' => ['story_1', 'story_2'],
            ],
            'Other platforms' => [
                'Link ad' => ['link'],
                'Pin' => ['pin'],
            ],
        ];

        foreach ([['Round 1', 'r1', false], ['Round 2', 'r2', true]] as [$roundName, $prefix, $active]) {
            $round = newFeedback::create([
                'category_id' => $category->id, 'name' => $roundName,
                'description' => $active ? 'Brighter photos, bigger headline.' : 'First concepts.',
                'is_active' => $active, 'is_approved' => false,
            ]);

            $concept = newFeedbackSet::create(['feedback_id' => $round->id, 'name' => 'Concept']);
            $boardSet = newVersion::create(['feedback_set_id' => $concept->id, 'name' => 'Storyboard']);
            foreach ($board as $pos => $shot) {
                newSocial::create([
                    'version_id' => $boardSet->id,
                    'name' => $shot,
                    'path' => "uploads/socials/demo/{$prefix}_board_".($pos + 1).'.jpg',
                    'placement' => 'storyboard',
                    'position' => $pos + 1,
                ]);
            }

            foreach ($layout as $versionName => $sets) {
                $version = newFeedbackSet::create(['feedback_id' => $round->id, 'name' => $versionName]);

                foreach ($sets as $setName => $files) {
                    $set = newVersion::create(['feedback_set_id' => $version->id, 'name' => $setName]);

                    foreach ($files as $pos => $file) {
                        // width / height are filled from the file by newSocial's saving hook.
                        newSocial::create([
                            'version_id' => $set->id,
                            'name' => "{$setName} ".($pos + 1),
                            'path' => "uploads/socials/demo/{$prefix}_{$file}.jpg",
                            'position' => $pos + 1,
                        ]);
                    }
                }
            }
        }
    }

    private function ratio(int $w, int $h): string
    {
        $gcd = (function ($a, $b) { while ($b) { [$a, $b] = [$b, $a % $b]; } return $a; })($w, $h);

        return ($w / $gcd).':'.($h / $gcd);
    }
}
