<?php

namespace App\Support;

/**
 * Human labels for the global settings, so the admin shows "Phone" rather than the raw
 * `phone_link` key the templates read. Unmapped keys fall back to a tidied-up key name.
 */
class SettingLabels
{
    /** Group title => [key => [label, hint]] */
    public const GROUPS = [
        'Brand' => [
            'logo' => ['Logo', 'Upload a PNG/SVG with a transparent background — it sits on the dark header and footer. Leave empty to keep the “DREAMTOUR” wordmark.'],
            'favicon' => ['Favicon', 'The small icon in the browser tab. A square PNG, SVG or ICO — 512×512 works best.'],
        ],
        'Company' => [
            'site_name' => ['Site name', null],
            'copyright' => ['Copyright text', 'Shown at the very bottom of the footer.'],
            'footer_description' => ['Footer description', 'The short line under the logo.'],
        ],
        'Contact' => [
            'phone' => ['Phone', 'Type it exactly as it should appear on the site.'],
            'phone_link' => ['Phone link', 'For click-to-call, no spaces: +14167266393'],
            'whatsapp' => ['WhatsApp number', 'Digits only.'],
            'email' => ['Email', null],
            'visa_email' => ['Visa email', 'Visa enquiries are sent to this address.'],
            'photos_email' => ['Photos email', 'Where visitors send their gallery photos.'],
            'address' => ['Address', null],
            'working_hours' => ['Working hours', null],
            'map_embed' => ['Map embed', 'Google Maps embed code or links.'],
        ],
        'Booking & payments' => [
            'payment_instructions' => ['Payment instructions', 'Shown on the booking page and in the confirmation email — e.g. the Interac e-Transfer address.'],
            'price_tax_note' => ['Price tax note', 'Shown after every price, e.g. “+ TAX”. Leave empty to hide.'],
            'whatsapp_label' => ['WhatsApp button text', 'Text on the floating WhatsApp button, e.g. “Chat with Jenn on WhatsApp”.'],
        ],
        'Navigation buttons' => [
            'header_tours_url' => ['Header: tours link', null],
            'header_book_url' => ['Header: book link', null],
        ],
    ];

    /** Multi-line fields render as a textarea. */
    public const LONG = ['footer_description', 'map_embed', 'address', 'payment_instructions'];

    /** Fields that accept a file upload as well as a pasted URL. */
    public const MEDIA = ['logo', 'favicon'];

    /** Media fields stored byte-for-byte instead of going through the WebP pipeline. */
    public const MEDIA_RAW = ['favicon'];

    public static function label(string $key): string
    {
        foreach (self::GROUPS as $keys) {
            if (isset($keys[$key])) {
                return $keys[$key][0];
            }
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    public static function hint(string $key): ?string
    {
        foreach (self::GROUPS as $keys) {
            if (isset($keys[$key])) {
                return $keys[$key][1];
            }
        }

        return null;
    }

    /** Group title => settings, with anything unmapped collected at the end. */
    public static function group($settings): array
    {
        $grouped = [];
        $mapped = [];

        foreach (self::GROUPS as $title => $keys) {
            foreach (array_keys($keys) as $key) {
                if ($setting = $settings->firstWhere('key', $key)) {
                    $grouped[$title][] = $setting;
                    $mapped[] = $key;
                }
            }
        }

        foreach ($settings as $setting) {
            if (! in_array($setting->key, $mapped, true)) {
                $grouped['Other'][] = $setting;
            }
        }

        return $grouped;
    }
}
