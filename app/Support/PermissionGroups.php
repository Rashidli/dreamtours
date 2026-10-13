<?php

namespace App\Support;

class PermissionGroups
{
    /** Section title => resource slugs, mirroring the admin sidebar. */
    public const SECTIONS = [
        'Access control' => ['users', 'roles', 'permissions'],
        'Website CMS' => ['pages', 'page-sections', 'navigation', 'settings', 'words', 'images', 'sliders', 'singles'],
        'Tours & CRM' => ['tours', 'tour-categories', 'destinations', 'transportations', 'pickup-points', 'tour-departures', 'tour-itineraries', 'tour-content', 'bookings', 'visa-services'],
        'Content' => ['company-content', 'abouts', 'reviews', 'faqs', 'gallery-albums', 'gallery-categories', 'gallery-media', 'contact_items', 'socials', 'contacts', 'blogs', 'portfolios', 'services', 'tags'],
    ];

    public const FALLBACK = 'Other';

    private const VERBS = ['list', 'create', 'edit', 'delete'];

    public static function sectionFor(string $permission): string
    {
        $resource = $permission;

        foreach (self::VERBS as $verb) {
            if (str_starts_with($permission, $verb.'-')) {
                $resource = substr($permission, strlen($verb) + 1);
                break;
            }
        }

        foreach (self::SECTIONS as $section => $resources) {
            if (in_array($resource, $resources, true)) {
                return $section;
            }
        }

        return self::FALLBACK;
    }

    /** Section titles in sidebar order, with anything unmapped last. */
    public static function sectionOrder(): array
    {
        return array_merge(array_keys(self::SECTIONS), [self::FALLBACK]);
    }
}
