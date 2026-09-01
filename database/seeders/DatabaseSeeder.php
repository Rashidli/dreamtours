<?php

namespace Database\Seeders;

use App\Models\Single;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        // Create permissions
        $permissions = [
            'list-users', 'create-users', 'edit-users', 'delete-users',
            'list-roles', 'create-roles', 'edit-roles', 'delete-roles',
            'list-permissions', 'create-permissions', 'edit-permissions', 'delete-permissions',
            'list-services', 'create-services', 'edit-services', 'delete-services',
            'list-blogs', 'create-blogs', 'edit-blogs', 'delete-blogs',
            'list-portfolios', 'create-portfolios', 'edit-portfolios', 'delete-portfolios',
            'list-tags', 'create-tags', 'edit-tags', 'delete-tags',
            'list-sliders', 'create-sliders', 'edit-sliders', 'delete-sliders',
            'list-faqs', 'create-faqs', 'edit-faqs', 'delete-faqs',
            'list-reviews', 'create-reviews', 'edit-reviews', 'delete-reviews',
            'list-abouts', 'create-abouts', 'edit-abouts', 'delete-abouts',
            'list-contacts', 'delete-contacts',
            'list-contact_items', 'create-contact_items', 'edit-contact_items', 'delete-contact_items',
            'list-socials', 'create-socials', 'edit-socials', 'delete-socials',
            'list-singles', 'create-singles', 'edit-singles', 'delete-singles',
            'list-words', 'create-words', 'edit-words', 'delete-words',
            'list-images', 'create-images', 'edit-images', 'delete-images',
            'list-settings', 'edit-settings',
            'list-pages', 'create-pages', 'edit-pages', 'delete-pages',
            'list-page-sections', 'create-page-sections', 'edit-page-sections', 'delete-page-sections',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all permissions to admin role
        $admin->givePermissionTo(Permission::all());

        // Create super admin user
        $user = User::updateOrCreate(['email' => 'superadmin@admin.com'], [
            'name' => 'Super Admin',
            'password' => Hash::make('Az12345678'),
        ]);
        $user->syncRoles(['superadmin']);

        // Create SEO pages (Singles)
        $seoPages = [
            ['type' => 'home', 'en' => ['title' => 'Home', 'slug' => 'home']],
            ['type' => 'about', 'en' => ['title' => 'About', 'slug' => 'about']],
            ['type' => 'services', 'en' => ['title' => 'Services', 'slug' => 'services']],
            ['type' => 'portfolio', 'en' => ['title' => 'Portfolio', 'slug' => 'portfolio']],
            ['type' => 'blogs', 'en' => ['title' => 'Blog', 'slug' => 'blog']],
            ['type' => 'contact', 'en' => ['title' => 'Contact', 'slug' => 'contact']],
        ];

        foreach ($seoPages as $page) {
            Single::updateOrCreate(['type' => $page['type']], $page);
        }

        $this->call(CmsSeeder::class);
        $this->call(CmsPermissionSeeder::class);
        $this->call(GlobalContentSeeder::class);
        $this->call(DreamTourContentSeeder::class);
        $this->call(UiCopySeeder::class);
    }
}
