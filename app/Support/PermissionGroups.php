<?php

namespace App\Support;

class PermissionGroups
{
    /** Section title => resource slugs, mirroring the admin sidebar. */
    public const SECTIONS = [
        'Access control' => ['users', 'roles', 'permissions', 'activity-log'],
        'Website CMS' => ['pages', 'page-sections', 'navigation', 'settings', 'words', 'images', 'sliders', 'singles'],
        'Tours & CRM' => ['payments', 'record-payments', 'confirm-payments', 'refund-payments', 'apply-discounts', 'view-financial-info', 'assign-departure-staff', 'assigned-departures', 'check-in-passengers', 'undo-check-in', 'leads', 'lead-stages', 'manage-lead-stages', 'customers', 'import-customers', 'merge-customers', 'export-customers', 'export-leads', 'export-bookings', 'view-customer-birthdays', 'manage-customer-labels', 'tours', 'tour-categories', 'tour-filters', 'destinations', 'transportations', 'pickup-points', 'vehicle-layouts', 'tour-departures', 'tour-itineraries', 'tour-content', 'bookings', 'visa-enquiries', 'visa-services'],
        'Content' => ['company-content', 'abouts', 'reviews', 'faqs', 'gallery-albums', 'gallery-categories', 'gallery-media', 'contact_items', 'socials', 'contacts', 'blogs', 'portfolios', 'services', 'tags'],
    ];

    public const FALLBACK = 'Other';

    private const VERBS = ['list', 'create', 'edit', 'delete'];

    /** Column titles for the role editor's View / Create / Edit / Delete grid. */
    public const VERB_LABELS = ['list' => 'View', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete'];

    /** Rows whose slug doesn't read well on its own. */
    public const RESOURCE_LABELS = [
        'activity-log' => 'Activity history', 'words' => 'UI copy', 'navigation' => 'Navigation & footer', 'pages' => 'Pages & SEO',
        'tour-content' => 'Tour content blocks', 'tour-departures' => 'Departure dates', 'bookings' => 'Bookings & enquiries',
        'company-content' => 'Homepage & company content', 'abouts' => 'About content', 'contact_items' => 'Contact information',
        'socials' => 'Social links', 'contacts' => 'Contact requests', 'visa-services' => 'Visa support pricing', 'vehicle-layouts' => 'Vehicle seating templates',
    ];

    /** Permissions that are not plain View / Create / Edit / Delete of one area: name => [label, what it allows]. */
    public const SPECIAL = [
        'view-financial-info' => ['View financial information', 'Prices, amounts paid and balances on bookings, customers and passenger lists.'],
        'list-payments' => ['Payments & balances reports', 'The ledger, outstanding balances and pending verification screens.'],
        'record-payments' => ['Record payments', 'Add a payment received by e-Transfer, cash, cheque or card.'],
        'confirm-payments' => ['Confirm or reject payments', 'Mark a pending payment as arrived, or reject it with a reason.'],
        'refund-payments' => ['Refund and void payments', 'Issue refunds and void payments entered by mistake.'],
        'override-capacity' => ['Book over capacity', 'Go past a date’s seat limit, with a reason that is kept in the activity history.'],
        'apply-discounts' => ['Apply discounts', 'Discounts, complimentary seats and price changes on a booking.'],
        'assign-departure-staff' => ['Assign trip staff', 'Choose the leaders, drivers and guides for each departure date.'],
        'list-assigned-departures' => ['My trips', 'See the departures this person is assigned to, with their passenger list. Nothing else.'],
        'check-in-passengers' => ['Check passengers in', 'Mark Checked in, Boarded, Not arrived or No-show on trips they can open.'],
        'undo-check-in' => ['Undo check-ins', 'Step back or clear any attendance mark. Without it people can only fix their own marks for 10 minutes.'],
        'export-customers' => ['Export customers', 'Download the customer list as CSV.'],
        'export-leads' => ['Export leads', 'Download the lead list (with its filters) as CSV or Excel.'],
        'export-bookings' => ['Export bookings', 'Download the booking list as CSV or Excel; amounts only with “View financial information”.'],
        'import-customers' => ['Import customers', 'Upload customer lists from CSV or Excel.'],
        'merge-customers' => ['Merge duplicate customers', 'Combine two records of the same person.'],
        'view-customer-birthdays' => ['See birthdays', 'Date of birth on customer records and the birthday filter.'],
        'manage-customer-labels' => ['Manage customer statuses & tags', 'Create, rename and archive them.'],
        'manage-lead-stages' => ['Manage lead stages', 'Change the columns of the lead board.'],
    ];
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

    /** What the default roles (CRM §16) are for, shown on the roles list. */
    public const ROLE_HINTS = [
        'superadmin' => 'Everything, including settings, roles and sensitive logs. Cannot be changed.',
        'admin' => 'Tours, departures, leads, customers, payments and content; technical settings only if granted.',
        'Sales Manager' => 'Leads, customer records, booking updates and the payment details sales needs.',
        'Content Manager' => 'Tour descriptions, itineraries, images and website pages. No bookings or finance.',
        'Accountant' => 'Payments, refunds, discounts and financial reports, with read access to bookings.',
        'Tour Leader' => 'Only the departures they are assigned to: schedule, pickups and passenger list.',
    ];

    /** Plain-language name of a permission, for lists and the activity history. */
    public static function label(string $permission): string
    {
        if (isset(self::SPECIAL[$permission])) {
            return self::SPECIAL[$permission][0];
        }
        [$verb, $resource] = self::split($permission);

        return $verb ? self::VERB_LABELS[$verb].' '.lcfirst(self::resourceLabel($resource)) : self::resourceLabel($permission);
    }

    public static function resourceLabel(string $resource): string
    {
        return self::RESOURCE_LABELS[$resource] ?? ucfirst(str_replace(['-', '_'], ' ', $resource));
    }

    /**
     * One section of the role editor: plain list/create/edit/delete permissions become grid rows
     * (resource => verb => Permission), everything else is listed with its explanation.
     *
     * @return array{rows: array<string, array<string, mixed>>, special: array<int, mixed>}
     */
    public static function grid(iterable $permissions): array
    {
        $rows = [];
        $special = [];
        foreach ($permissions as $permission) {
            [$verb, $resource] = self::split($permission->name);
            if ($verb && !isset(self::SPECIAL[$permission->name])) {
                $rows[$resource][$verb] = $permission;
            } else {
                $special[] = $permission;
            }
        }
        uksort($rows, fn ($a, $b) => strcasecmp(self::resourceLabel($a), self::resourceLabel($b)));

        return ['rows' => $rows, 'special' => $special];
    }

    /** @return array{0: ?string, 1: string} verb (or null) and resource slug */
    private static function split(string $permission): array
    {
        foreach (self::VERBS as $verb) {
            if (str_starts_with($permission, $verb.'-')) {
                return [$verb, substr($permission, strlen($verb) + 1)];
            }
        }

        return [null, $permission];
    }

    /** Section titles in sidebar order, with anything unmapped last. */
    public static function sectionOrder(): array
    {
        return array_merge(array_keys(self::SECTIONS), [self::FALLBACK]);
    }
}
