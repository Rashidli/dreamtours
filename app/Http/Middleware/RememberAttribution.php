<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers where a visitor came from — UTM tags, ad click IDs, the first page
 * they landed on and the site that sent them — so a request they send later in the
 * visit becomes a lead with its campaign (§3, §20). First touch wins; a new set of
 * UTM tags (another ad) replaces it. Admin pages are ignored.
 */
class RememberAttribution
{
    public const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && !$request->is('admin', 'admin/*') && $request->hasSession()) {
            $tags = array_filter(array_map(fn ($v) => is_string($v) ? mb_substr(trim($v), 0, 150) : null, $request->only(self::KEYS)));
            // Ad clicks without UTM tags still say where they came from.
            if (!isset($tags['utm_source']) && ($request->filled('gclid') || $request->filled('fbclid'))) {
                $tags += ['utm_source' => $request->filled('gclid') ? 'google' : 'facebook', 'utm_medium' => 'cpc'];
            }
            if ($tags || !$request->session()->has('attribution')) {
                $referrer = (string) $request->headers->get('referer');
                $external = $referrer !== '' && parse_url($referrer, PHP_URL_HOST) !== $request->getHost();
                $request->session()->put('attribution', $tags + [
                    'landing_page' => mb_substr($request->fullUrl(), 0, 500),
                    'referrer' => $external ? mb_substr($referrer, 0, 500) : null,
                ]);
            }
        }

        return $next($request);
    }
}
