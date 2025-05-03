<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Designation;
use App\Models\Route;
use App\Models\ColorPalette;
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
        // User::factory(10)->create();

        User::create([
            'name' => 'Test User',
            'email' => 'test@planetnine.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'designation' => 1,
            'permissions' => ['*'],
        ]);

        $designations = [
            'Developer',
            'Project Manager',
            'Designer',
            'Quality Assurance',
            'Data Scientist',
            'Office Staff'
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
        
    }
}
