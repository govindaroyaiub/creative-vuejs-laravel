<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Designation;
use App\Models\Route;
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
            'name' => 'Govinda Roy',
            'email' => 'govinda@planetnine.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'designation' => 1,
            'permissions' => ['*'],
        ]);

        User::create([
            'name' => 'Govinda Roy',
            'email' => 'test@planetnine.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'designation' => 2,
            'permissions' => ['*'],
        ]);

        $designations = [
            'Developer',
            'Manager',
            'Designer',
            'QA',
            'Data Scientist',
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
            ['title' => 'Social Image Formats', 'href' => '/socials'],
            ['title' => 'File Transfers', 'href' => '/file-transfers'],
            ['title' => 'Bills', 'href' => '/bills'],
            ['title' => 'Designations', 'href' => '/user-managements/designations'],
            ['title' => 'Users', 'href' => '/user-managements/users'],
            ['title' => 'Routes', 'href' => '/user-managements/routes'],
            ['title' => 'Clients', 'href' => '/clients']

        ];

        foreach ($routes as $route) {
            Route::create($route);
        }
    }
}
