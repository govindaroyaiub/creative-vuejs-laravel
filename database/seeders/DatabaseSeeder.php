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
                'name' => 'Desert Sand',
                'primary' => '#e2d39a',
                'secondary' => '#fefbf0',
                'tertiary' => '#99804f',
                'quaternary' => '#ffffff',
                'quinary' => '#99804f',
                'senary' => '#99804f',
                'septenary' => '#99804f',
                'feedbackTab_inactive_image' => 'uploads/colorPalette/68dd3140d647a_Gold Light_.png',
                'feedbackTab_active_image' => 'uploads/colorPalette/68dd3140d6713_Gold Dark.png',
                'rightSideTab_feedback_description_image' => 'uploads/colorPalette/68ee4ad6e0b76_Preview-System-13.png',
                'rightSideTab_color_palette_image' => 'uploads/colorPalette/68ee4ad6e6538_Preview-System-132.png',
                'header_image' => 'uploads/colorPalette/68dd02be1699f_gold_header_image.png',
                'status' => 1,
            ],
            [
                'name' => 'Skyline Blue',
                'primary' => '#9acde2',
                'secondary' => '#f3fbfc',
                'tertiary' => '#4f8d99',
                'quaternary' => '#ffffff',
                'quinary' => '#4f8d99',
                'senary' => '#4f8d99',
                'septenary' => '#4f8d99',
                'feedbackTab_inactive_image' => 'uploads/colorPalette/68dd31b888fa0_Blue Light_.png',
                'feedbackTab_active_image' => 'uploads/colorPalette/68dd31b8892ca_Blue Dark.png',
                'rightSideTab_feedback_description_image' => 'uploads/colorPalette/68dd072d232d4_blue_right_feedback_description.png',
                'rightSideTab_color_palette_image' => 'uploads/colorPalette/68dd072d233e4_blue_right_color.png',
                'header_image' => 'uploads/colorPalette/68dd072d23455_blue_header_image.png',
                'status' => 1,
            ],
            [
                'name' => 'Terra Cotta Orange',
                'primary' => '#ea9f76',
                'secondary' => '#fdf4e5',
                'tertiary' => '#e65100',
                'quaternary' => '#ffffff',
                'quinary' => '#e65100',
                'senary' => '#e65100',
                'septenary' => '#e65100',
                'feedbackTab_inactive_image' => 'uploads/colorPalette/68ff713cda7f3_68dd3140d647a_Gold Light_.png',
                'feedbackTab_active_image' => 'uploads/colorPalette/68ff713cdae41_68dd3140d6713_Gold Dark.png',
                'rightSideTab_feedback_description_image' => 'uploads/colorPalette/68ff713cdb284_feedback-message.png',
                'rightSideTab_color_palette_image' => 'uploads/colorPalette/68ff713cdb6c5_colorpalette.png',
                'header_image' => 'uploads/colorPalette/68ff713cdba9c_Preview-System-14.png',
                'status' => 1,
            ],
            [
                'name' => 'Lemon Haze',
                'primary' => '#afc89d',
                'secondary' => '#f6fbf2',
                'tertiary' => '#6f8357',
                'quaternary' => '#ffffff',
                'quinary' => '#6f8357',
                'senary' => '#6f8357',
                'septenary' => '#6f8357',
                'feedbackTab_inactive_image' => 'uploads/colorPalette/68dd3656a7b8b_Green Light_.png',
                'feedbackTab_active_image' => 'uploads/colorPalette/68dd3656a7c86_Green Dark.png',
                'rightSideTab_feedback_description_image' => 'uploads/colorPalette/68dcf5ee6ff74_green_rightTab_feedback_description_image.png',
                'rightSideTab_color_palette_image' => 'uploads/colorPalette/68dcf5ee6fff7_green_righTab_color_pallete_image.png',
                'header_image' => 'uploads/colorPalette/68dcf5ee7006b_green_header_image.png',
                'status' => 1,
            ],
            [
                'name' => 'Purple Haze',
                'primary' => '#b38dd9',
                'secondary' => '#f6f0fc',
                'tertiary' => '#624694',
                'quaternary' => '#ffffff',
                'quinary' => '#624694',
                'senary' => '#624694',
                'septenary' => '#624694',
                'feedbackTab_inactive_image' => 'uploads/colorPalette/68dd31d020b72_Purple Light_.png',
                'feedbackTab_active_image' => 'uploads/colorPalette/68dd31d020f92_Purple Dark.png',
                'rightSideTab_feedback_description_image' => 'uploads/colorPalette/68dcf8d91f377_purple_feedback.png',
                'rightSideTab_color_palette_image' => 'uploads/colorPalette/68dcf8d91f466_purple_color.png',
                'header_image' => 'uploads/colorPalette/68dcf8d91f543_purple_header_image.png',
                'status' => 1,
            ],
            [
                'name' => 'Velocity Red',
                'primary' => '#c60b1d',
                'secondary' => '#ffffff',
                'tertiary' => '#e30613',
                'quaternary' => '#000000',
                'quinary' => '#c60b1d',
                'senary' => '#c60b1d',
                'septenary' => '#c60b1d',
                'feedbackTab_inactive_image' => 'uploads/colorPalette/68dd3661b01c3_Purple Light_.png',
                'feedbackTab_active_image' => 'uploads/colorPalette/68dd3661b0570_Purple Dark.png',
                'rightSideTab_feedback_description_image' => 'uploads/colorPalette/68dcf7ffebda4_red_rightTab_feedbak.png',
                'rightSideTab_color_palette_image' => 'uploads/colorPalette/68dcf7ffebe18_red_rightTab_color.png',
                'header_image' => 'uploads/colorPalette/68dcf7ffebe83_red_header_image.png',
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
                'logo' => '1759917729_P9 Logo 350X180.png',
                'color_palette_id' => 5
            ],
            [
                'name' => 'Deka Mart',
                'website' => 'https://dekamart.nl',
                'preview_url' => 'https://creative.planetnine.com',
                'logo' => '1759917760_Deka Markt.png',
                'color_palette_id' => 6
            ],
            [
                'name' => 'DIRK',
                'website' => 'https://dirk.nl',
                'preview_url' => 'https://creative.planetnine.com',
                'logo' => '1759917780_DIRK.png',
                'color_palette_id' => 6
            ],
            [
                'name' => 'Hoogvliet',
                'website' => 'https://hoogvliet.nl',
                'preview_url' => 'https://creative.planetnine.com',
                'logo' => '1759917802_Hoogvliet.png',
                'color_palette_id' => 2
            ],
            [
                'name' => 'Talpa - Radio 10',
                'website' => 'https://radio10.nl',
                'preview_url' => 'https://creative.planetnine.com',
                'logo' => '1759917853_Radio 10.png',
                'color_palette_id' => 5
            ],
            [
                'name' => 'Talpa - Radio 538',
                'website' => 'https://radio538.nl',
                'preview_url' => 'https://creative.planetnine.com',
                'logo' => '1759917880_RADIO 538.png',
                'color_palette_id' => 5
            ],
            [
                'name' => 'Talpa - Sky Radio',
                'website' => 'https://skyradio.nl',
                'preview_url' => 'https://creative.planetnine.com',
                'logo' => '1759917903_Talpa Sky Radio.png',
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
            ['title' => 'Media Library', 'href' => '/medias'],
            ['title' => 'Activity Logs', 'href' => '/activity-logs'],
            ['title' => 'Tetris', 'href' => '/play/tetris'],
            ['title' => 'Documentation', 'href' => '/lazyDoc'],
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
                'name' => '3840x2160',
                'width' => '3840',
                'height' => '2160',
            ],
            [
                'name' => '2160x3840',
                'width' => '2160',
                'height' => '3840',
            ],
            [
                'name' => '1920x1080',
                'width' => '1920',
                'height' => '1080',
            ],
            [
                'name' => '1440x1080',
                'width' => '1440',
                'height' => '1080',
            ],
            [
                'name' => '1280x720',
                'width' => '1280',
                'height' => '720',
            ],
            [
                'name' => '1080x1080',
                'width' => '1080',
                'height' => '1080',
            ],
            [
                'name' => '1080x1920',
                'width' => '1080',
                'height' => '1920',
            ],
            [
                'name' => '1080x1350',
                'width' => '1080',
                'height' => '1350',
            ],
            [
                'name' => '1080x1920',
                'width' => '1080',
                'height' => '1920',
            ],
            [
                'name' => '1080x1536',
                'width' => '1080',
                'height' => '1536',
            ],
            [
                'name' => '1080x2536',
                'width' => '1080',
                'height' => '2536',
            ],
            [
                'name' => '720x1280',
                'width' => '720',
                'height' => '1280',
            ],
            [
                'name' => '416x346',
                'width' => '416',
                'height' => '346',
            ],
            [
                'name' => '336x280',
                'width' => '336',
                'height' => '280',
            ],
            [
                'name' => '328x574',
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
