<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * The default staff roles of CRM §16. Missing roles are created with these permissions;
 * a role that already exists is left as the team edited it unless a reset is asked for.
 */
class StaffRoles
{
    /** Permissions introduced with staff roles and trip assignments. */
    public const PERMISSIONS = ['view-financial-info', 'assign-departure-staff', 'list-assigned-departures'];

    public const DEFAULTS = [
        'Sales Manager' => [
            'list-leads', 'create-leads', 'edit-leads',
            'list-customers', 'create-customers', 'edit-customers', 'merge-customers', 'view-customer-birthdays',
            'list-bookings', 'edit-bookings', 'list-payments', 'record-payments', 'view-financial-info',
            'list-tours', 'list-tour-departures', 'list-visa-enquiries', 'edit-visa-enquiries', 'list-contacts',
        ],
        'Content Manager' => [
            'list-tours', 'create-tours', 'edit-tours', 'list-tour-content', 'create-tour-content', 'edit-tour-content', 'delete-tour-content',
            'list-tour-itineraries', 'create-tour-itineraries', 'edit-tour-itineraries', 'delete-tour-itineraries', 'list-tour-departures',
            'list-tour-categories', 'create-tour-categories', 'edit-tour-categories', 'list-tour-filters', 'create-tour-filters', 'edit-tour-filters',
            'list-inclusions', 'create-inclusions', 'edit-inclusions', 'list-destinations', 'create-destinations', 'edit-destinations',
            'list-pickup-points', 'list-transportations',
            'list-pages', 'create-pages', 'edit-pages', 'list-page-sections', 'create-page-sections', 'edit-page-sections', 'delete-page-sections',
            'list-navigation', 'create-navigation', 'edit-navigation', 'list-words', 'create-words', 'edit-words',
            'list-company-content', 'create-company-content', 'edit-company-content', 'delete-company-content',
            'list-abouts', 'create-abouts', 'edit-abouts', 'list-reviews', 'create-reviews', 'edit-reviews',
            'list-faqs', 'create-faqs', 'edit-faqs', 'delete-faqs',
            'list-gallery-albums', 'create-gallery-albums', 'edit-gallery-albums', 'delete-gallery-albums',
            'list-gallery-categories', 'create-gallery-categories', 'edit-gallery-categories',
            'list-gallery-media', 'create-gallery-media', 'edit-gallery-media', 'delete-gallery-media',
            'list-visa-services', 'edit-visa-services',
        ],
        'Accountant' => [
            'list-bookings', 'list-payments', 'record-payments', 'confirm-payments', 'refund-payments', 'apply-discounts', 'view-financial-info', 'export-bookings',
            'list-customers', 'export-customers', 'list-tours', 'list-tour-departures',
        ],
        'Tour Leader' => ['list-assigned-departures', 'check-in-passengers'],
    ];

    /**
     * Create the permissions above and any missing default role; returns the roles created.
     * $topUp also adds missing default permissions to existing default roles (never removes any):
     * the seeder uses it on fresh databases, where the migration ran before the base permissions existed.
     */
    public static function install(bool $topUp = false): array
    {
        $now = now();
        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        if ($admin = DB::table('roles')->where(['name' => 'admin', 'guard_name' => 'web'])->value('id')) {
            self::grant($admin, self::PERMISSIONS);
        }
        $created = [];
        foreach (self::DEFAULTS as $role => $permissions) {
            if ($existing = DB::table('roles')->where(['name' => $role, 'guard_name' => 'web'])->value('id')) {
                if ($topUp) {
                    self::grant($existing, $permissions);
                }
                continue;
            }
            self::grant(DB::table('roles')->insertGetId(['name' => $role, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]), $permissions);
            $created[] = $role;
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }

    /** @return string[]|null the default permission names of a role, or null if it isn't a default role */
    public static function defaultsFor(string $role): ?array
    {
        return self::DEFAULTS[$role] ?? null;
    }

    private static function grant(int $roleId, array $permissions): void
    {
        foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }
    }
}
