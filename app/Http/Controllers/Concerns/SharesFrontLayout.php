<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ContactItem;
use App\Models\NavigationItem;
use App\Models\Setting;
use App\Models\Social;

/** The data every public page's header and footer read. */
trait SharesFrontLayout
{
    protected function layoutData(): array
    {
        return [
            'siteSettings' => Setting::query()->pluck('value', 'key'),
            'headerNavigation' => NavigationItem::where('location', 'header')->whereNull('parent_id')->where('is_active', true)
                ->with(['translations', 'children' => fn ($query) => $query->where('is_active', true)->with('translations')])
                ->orderBy('sort_order')->get(),
            'footerNavigation' => NavigationItem::whereIn('location', ['footer_company', 'footer_support', 'footer_legal'])
                ->where('is_active', true)->with('translations')->orderBy('sort_order')->get()->groupBy('location'),
            'contactItems' => ContactItem::active()->ordered()->with('translations')->get(),
            'socials' => Social::active()->ordered()->get(),
        ];
    }
}
