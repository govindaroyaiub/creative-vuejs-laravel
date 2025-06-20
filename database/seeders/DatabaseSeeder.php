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
                'name' => 'Gold Theme', 'primary' => '#e2d39a', 'secondary' => '#fbf5de', 'tertiary' => '#a67c00', 'quaternary' => '#a67c00',
                'status' => 1,
            ],
            [
                'name' => 'Sky Blue Theme',
                'primary' => '#98c1d9',
                'secondary' => '#e0fbfc',
                'tertiary' => '#3d5a80',
                'quaternary' => '#3d5a80',
                'status' => 1,
            ],
            [
                'name' => 'Desert Theme',
                'primary' => '#cfb096',
                'secondary' => '#f2e3d6',
                'tertiary' => '#8a624f',
                'quaternary' => '#8a624f',
                'status' => 1,
            ],
            [
                'name' => 'Green Theme',
                'primary' => '#afc89d',
                'secondary' => '#eaf5e2',
                'tertiary' => '#6f8357',
                'quaternary' => '#6f8357',
                'status' => 1,
            ],
            [
                'name' => 'Planet Nine Theme',
                'primary' => '#a0abd1',
                'secondary' => '#cbcef5',
                'tertiary' => '#57698a',
                'quaternary' => '#57698a',
                'status' => 1,
            ],
            [
                'name' => 'Dirk/Deka Theme',
                'primary' => '#ed1c24',
                'secondary' => '#ffdfe0',
                'tertiary' => '#a1070d',
                'quaternary' => '#a1070d',
                'status' => 1,
            ],
        ];


        foreach ($colorPalettes as $palette) {
            ColorPalette::create($palette);
        }

        $clients = [
            [
                'name' => 'Planet Nine', 
                'website' => 'https://www.planetnine.com', 
                'preview_url' => 'https://preview.creative-planetnine.com', 
                'logo' => 'planetnine.png', 
                'color_palette_id' => 5
            ],
        ];

        foreach ($clients as $client) {
            Client::create([
                'name' => $client['name'],
                'website' => $client['website'],
                'preview_url' => $client['preview_url'],
                'logo' => $client['logo'],
                'color_palette_id' => $client['color_palette_id'],
            ]);
        }

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

        $users = [
            [
                'name' => 'Govinda Roy',
                'email' => 'govinda@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'designation' => 1,
                'permissions' => ['*'],
                'client_id' => 1,
            ],
            [
                'name' => 'Ibrahim Faisal',
                'email' => 'faisal@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'designation' => 1,
                'permissions' => ['*'],
                'client_id' => 1,
            ],
            [
                'name' => 'Rohit Hasan',
                'email' => 'rohit@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'designation' => 5,
                'permissions' => ['*'],
                'client_id' => 1,
            ],
            [
                'name' => 'Rashid Shaharier',
                'email' => 'rashid@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'designation' => 3,
                'permissions' => ['*'],
                'client_id' => 1,
            ],
            [
                'name' => 'Rokib Hasan',
                'email' => 'rokib@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'designation' => 3,
                'permissions' => ['*'],
                'client_id' => 1,
            ],
            [
                'name' => 'Sunjida Khanom',
                'email' => 'sunjida@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'designation' => 1,
                'permissions' => ['*'],
                'client_id' => 1,
            ],
            [
                'name' => 'Faria Mahmud',
                'email' => 'faria@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'designation' => 1,
                'permissions' => ['*'],
                'client_id' => 1,
            ],
            [
                'name' => 'Limon Roy',
                'email' => 'limon@planetnine.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'designation' => 6,
                'permissions' => ['*'],
                'client_id' => 1,
            ]
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => $user['password'],
                'role' => $user['role'],
                'designation' => $user['designation'],
                'permissions' => $user['permissions'],
                'client_id' => $user['client_id'],
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
            ['width' => '120', 'height' => '240'],
            ['width' => '120', 'height' => '600'],
            ['width' => '160', 'height' => '600'],
            ['width' => '180', 'height' => '150'],
            ['width' => '200', 'height' => '200'],
            ['width' => '212', 'height' => '177'],
            ['width' => '234', 'height' => '60'],
            ['width' => '250', 'height' => '250'],
            ['width' => '262', 'height' => '184'],
            ['width' => '300', 'height' => '600'],
            ['width' => '300', 'height' => '120'],
            ['width' => '300', 'height' => '50'],
            ['width' => '300', 'height' => '60'],
            ['width' => '300', 'height' => '250'],
            ['width' => '305', 'height' => '325'],
            ['width' => '306', 'height' => '230'],
            ['width' => '306', 'height' => '325'],
            ['width' => '320', 'height' => '240'],
            ['width' => '320', 'height' => '50'],
            ['width' => '320', 'height' => '100'],
            ['width' => '320', 'height' => '480'],
            ['width' => '336', 'height' => '280'],
            ['width' => '468', 'height' => '60'],
            ['width' => '500', 'height' => '500'],
            ['width' => '580', 'height' => '400'],
            ['width' => '600', 'height' => '100'],
            ['width' => '600', 'height' => '700'],
            ['width' => '728', 'height' => '90'],
            ['width' => '768', 'height' => '1024'],
            ['width' => '960', 'height' => '300'],
            ['width' => '970', 'height' => '500'],
            ['width' => '970', 'height' => '250'],
            ['width' => '970', 'height' => '90'],
            ['width' => '1024', 'height' => '768'],
            ['width' => '1080', 'height' => '1080'],
            ['width' => '1115', 'height' => '300'],
            ['width' => '1272', 'height' => '328'],
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
