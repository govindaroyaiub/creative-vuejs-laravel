<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Designation;
use App\Models\Route;
use App\Models\Client;
use App\Models\Preview;
use App\Models\ColorPalette;
use App\Models\BannerSize;
use App\Models\VideoSize;
use App\Models\Social;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $colorPalettes = [
            [
                'name' => 'Gold Theme',
                'primary' => '#e2d39a',
                'secondary' => '#fbf5de',
                'tertiary' => '#e2d39a',
                'quaternary' => '#e2d39a',
                'status' => 1,
            ],
            [
                'name' => 'Sky Blue Theme',
                'primary' => '#9acde2',
                'secondary' => '#c0f8fc',
                'tertiary' => '#4f8d99',
                'quaternary' => '#ffffff',
                'status' => 1,
            ],
            [
                'name' => 'Desert Theme',
                'primary' => '#cfb096',
                'secondary' => '#f2e3d6',
                'tertiary' => '#8a624f',
                'quaternary' => '#d9bca4',
                'status' => 1,
            ],
            [
                'name' => 'Green Theme',
                'primary' => '#afc89d',
                'secondary' => '#eaf5e2',
                'tertiary' => '#6f8357',
                'quaternary' => '#c4dcb3',
                'status' => 1,
            ],
            [
                'name' => 'Planet Nine Theme',
                'primary' => '#a0abd1',
                'secondary' => '#cbcef5',
                'tertiary' => '#57698a',
                'quaternary' => '#b3b6e2',
                'status' => 1,
            ],
            [
                'name' => 'Rose Theme',
                'primary' => '#c89699',
                'secondary' => '#fbe0e2',
                'tertiary' => '#84515b',
                'quaternary' => '#f1c1c4',
                'status' => 1,
            ],
        ];


        foreach ($colorPalettes as $palette) {
            ColorPalette::create($palette);
        }

        $clients = [
            ['name' => 'Planet Nine', 'website' => 'https://www.planetnine.com', 'preview_url' => 'https://preview.creative-planetnine.com', 'logo' => 'planetnine.png', 'color_palette_id' => 5],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }

        User::create([
            'name' => 'Test User',
            'email' => 'test@planetnine.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'designation' => 1,
            'permissions' => ['*'],
            'client_id' => 1,
        ]);

        $designations = [
            'Developer',
            'Project Manager',
            'Designer',
            'Quality Assurance',
            'Data Scientist',
            'Office Staff',
            'Client'
        ];

        foreach ($designations as $designation) {
            Designation::create([
                'name' => $designation,
            ]);
        }

        $routes = [
            ['title' => 'Dashboard', 'href' => '/dashboard'],
            ['title' => 'Previews', 'href' => '/previews'],
            ['title' => 'Banner Sizes', 'href' => '/banner-sizes'],
            ['title' => 'Video Sizes', 'href' => '/video-sizes'],
            ['title' => 'Social Formats', 'href' => '/socials'],
            ['title' => 'File Transfers', 'href' => '/file-transfers'],
            ['title' => 'Bills', 'href' => '/bills'],
            ['title' => 'Access Manager', 'href' => '/user-managements'],
            ['title' => 'Clients', 'href' => '/clients'],
            ['title' => 'Registration', 'href' => '/welcome-to-planetnine/register'],
            ['title' => 'Change Password', 'href' => '/change-password'],
            ['title' => 'Color Palettes', 'href' => '/color-palettes'],
            ['title' => 'Media Library', 'href' => '/medias']
        ];

        foreach ($routes as $route) {
            Route::create($route);
        }

        $bannerSizes = [
            [
                'width' => '300',
                'height' => '250',
            ],
            [
                'width' => '728',
                'height' => '90',
            ],
            [
                'width' => '160',
                'height' => '600',
            ],
            [
                'width' => '300',
                'height' => '600',
            ],
            [
                'width' => '970',
                'height' => '250',
            ],
            [
                'width' => '970',
                'height' => '90',
            ],
            [
                'width' => '468',
                'height' => '60',
            ],
            [
                'width' => '1200',
                'height' => '628',
            ],
            [
                'width' => '1200',
                'height' => '1200',
            ],
            [
                'width' => '1080',
                'height' => '1080',
            ],
            [
                'width' => '1080',
                'height' => '1350',
            ],
            [
                'width' => '1080',
                'height' => '1920',
            ],
            [
                'width' => '1200',
                'height' => '1200',
            ],
            [
                'width' => '1200',
                'height' => '628',
            ],
            [
                'width' => '1200',
                'height' => '900',
            ],
            [
                'width' => '1200',
                'height' => '1500',
            ],
            [
                'width' => '1200',
                'height' => '1800',
            ],
        ];

        foreach ($bannerSizes as $size) {
            BannerSize::create($size);
        }

        $videoSizes = [
            [
                'name' => 'Video',
                'width' => '3840',
                'height' => '2160',
            ],
            [
                'name' => 'Video',
                'width' => '2160',
                'height' => '3840',
            ],
            [
                'name' => 'Youtube Bumper Ad 6"',
                'width' => '1920',
                'height' => '1080',
            ],
            [
                'name' => 'Youtube Pre-Roll 15" (Skippable)',
                'width' => '1920',
                'height' => '1080',
            ],
            [
                'name' => 'Youtube Pre-Roll 20" (Skippable)',
                'width' => '1920',
                'height' => '1080',
            ],
            [
                'name' => '1440x1080',
                'width' => '1440',
                'height' => '1080',
            ],
            [
                'name' => 'Facebook Shared Post Video Landscape 15"',
                'width' => '1280',
                'height' => '720',
            ],
            [
                'name' => 'Facebook / Instagram Social Video 1:1 15"',
                'width' => '1080',
                'height' => '1080',
            ],
            [
                'name' => 'Facebook Carousel Video 15" ',
                'width' => '1080',
                'height' => '1080',
            ],
            [
                'name' => 'Facebook / Instagram Social Video 1:1 6',
                'width' => '1080',
                'height' => '1080',
            ],
            [
                'name' => 'Logo animation',
                'width' => '1080',
                'height' => '1080',
            ],
            [
                'name' => 'Social Video 6"',
                'width' => '1080',
                'height' => '1920',
            ],
            [
                'name' => 'Social Video 9:16 1080x1920',
                'width' => '1080',
                'height' => '1920',
            ],
            [
                'name' => 'Facebook Video',
                'width' => '1080',
                'height' => '1350',
            ],
            [
                'name' => 'Youtube Portrait for Mobile Devices',
                'width' => '1080',
                'height' => '1920',
            ],
            [
                'name' => 'Social Video 10 Sec',
                'width' => '1080',
                'height' => '1920',
            ],
            [
                'name' => 'Video',
                'width' => '1080',
                'height' => '1536',
            ],
            [
                'name' => 'Video 6sec',
                'width' => '1080',
                'height' => '2536',
            ],
            [
                'name' => 'Facebook Shared Post Video Portrait 15"',
                'width' => '720',
                'height' => '1280',
            ],
            [
                'name' => 'Video',
                'width' => '416',
                'height' => '346',
            ],
            [
                'name' => 'Video',
                'width' => '336',
                'height' => '280',
            ],
            [
                'name' => 'Video',
                'width' => '328',
                'height' => '574',
            ]
        ];

        foreach ($videoSizes as $size) {
            VideoSize::create($size);
        }

        $socials = [
            ['name' => 'Storyboard'],
            ['name' => 'Instagram'],
            ['name' => 'Facebook'],
            ['name' => 'Twitter'],
            ['name' => 'LinkedIn'],
            ['name' => 'YouTube'],
            ['name' => 'Snapchat'],
            ['name' => 'TikTok'],
            ['name' => 'Pinterest'],
            ['name' => 'WhatsApp'],
            ['name' => 'Telegram'],
            ['name' => 'Discord'],
            ['name' => 'Reddit'],
            ['name' => 'Tumblr'],
            ['name' => 'Flickr'],
            ['name' => 'Vimeo'],
            ['name' => 'Behance'],
            ['name' => 'Dribbble'],
            ['name' => 'Quora'],
            ['name' => 'Medium'],
            ['name' => 'Clubhouse'],
            ['name' => 'Google My Business'],
            ['name' => 'Yelp'],
            ['name' => 'Tripadvisor'],
            ['name' => 'Spotify'],
            ['name' => 'SoundCloud'],
        ];

        foreach ($socials as $social) {
            Social::create($social);
        }
    }
}
