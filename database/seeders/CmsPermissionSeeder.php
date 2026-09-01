<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CmsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'list-pages', 'create-pages', 'edit-pages', 'delete-pages',
            'list-page-sections', 'create-page-sections', 'edit-page-sections', 'delete-page-sections',
            'list-navigation', 'create-navigation', 'edit-navigation', 'delete-navigation',
            'list-tour-categories', 'create-tour-categories', 'edit-tour-categories', 'delete-tour-categories',
            'list-destinations', 'create-destinations', 'edit-destinations', 'delete-destinations',
            'list-transportations', 'create-transportations', 'edit-transportations', 'delete-transportations',
            'list-pickup-points', 'create-pickup-points', 'edit-pickup-points', 'delete-pickup-points',
            'list-tours', 'create-tours', 'edit-tours', 'delete-tours',
            'list-tour-departures', 'create-tour-departures', 'edit-tour-departures', 'delete-tour-departures',
            'list-tour-itineraries', 'create-tour-itineraries', 'edit-tour-itineraries', 'delete-tour-itineraries',
            'list-tour-content', 'create-tour-content', 'edit-tour-content', 'delete-tour-content',
            'list-bookings', 'edit-bookings', 'delete-bookings',
            'list-visa-services', 'create-visa-services', 'edit-visa-services', 'delete-visa-services',
            'list-gallery-categories', 'create-gallery-categories', 'edit-gallery-categories', 'delete-gallery-categories',
            'list-gallery-albums', 'create-gallery-albums', 'edit-gallery-albums', 'delete-gallery-albums',
            'list-gallery-media', 'create-gallery-media', 'edit-gallery-media', 'delete-gallery-media',
            'list-company-content', 'create-company-content', 'edit-company-content', 'delete-company-content',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::where('name', 'admin')->first()?->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
