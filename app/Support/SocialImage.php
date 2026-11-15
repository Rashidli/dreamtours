<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Link-preview image (Open Graph) for Facebook, Instagram, WhatsApp, Telegram, X…
 * Those apps want a 1200×630 JPEG or PNG well under 300 KB; many ignore WebP and
 * fall back to the favicon. The card is built once and cached on the public disk
 * under social/, keyed by the logo file, so uploading a new logo makes a new card.
 */
class SocialImage
{
    public const WIDTH = 1200;
    public const HEIGHT = 630;

    /** The site logo centred on the dark brand background, with a pink bar along the bottom. */
    public static function brand(): ?string
    {
        return self::build((string) setting('logo'), 'brand', function (\GdImage $logo) {
            $card = self::canvas();
            imagefilledrectangle($card, 0, self::HEIGHT - 12, self::WIDTH, self::HEIGHT, imagecolorallocate($card, 0xF8, 0x0A, 0x6A));
            $scale = min(860 / imagesx($logo), 300 / imagesy($logo), 1);
            [$w, $h] = [(int) round(imagesx($logo) * $scale), (int) round(imagesy($logo) * $scale)];
            imagecopyresampled($card, $logo, intdiv(self::WIDTH - $w, 2), intdiv(self::HEIGHT - 12 - $h, 2), 0, 0, $w, $h, imagesx($logo), imagesy($logo));

            return $card;
        });
    }

    /** Public URL of the cached JPEG, building it on first use; null when the source can't be read. */
    private static function build(string $source, string $kind, callable $draw): ?string
    {
        $disk = Storage::disk('public');
        if ($source === '' || str_contains($source, '://') || !$disk->exists($source)) {
            return null;
        }
        $target = 'social/'.$kind.'-'.substr(md5($source.'|'.$disk->lastModified($source).'|v1'), 0, 16).'.jpg';
        if (!$disk->exists($target)) {
            try {
                $image = @imagecreatefromstring($disk->get($source));
                if (!$image) {
                    return null;
                }
                $card = $draw($image);
                ob_start();
                imagejpeg($card, null, 86);
                $disk->put($target, ob_get_clean());
            } catch (\Throwable $e) {
                Log::warning('Social preview image could not be built', ['source' => $source, 'error' => $e->getMessage()]);

                return null;
            }
        }

        return $disk->url($target);
    }

    private static function canvas(): \GdImage
    {
        $card = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagefill($card, 0, 0, imagecolorallocate($card, 0x14, 0x18, 0x24));

        return $card;
    }
}
