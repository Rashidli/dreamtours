<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use SplFileInfo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageUploadService
{
    /**
     * Store an uploaded image as WebP on the public disk and return its stored name.
     */
    public function upload($file): string
    {
        $manager = new ImageManager(new Driver());

        $image = ($file instanceof SplFileInfo ? $manager->decodeSplFileInfo($file) : $manager->decodePath((string) $file))
            ->scaleDown(width: 2400);

        $filename = Str::uuid() . '.webp';
        Storage::disk('public')->put($filename, (string) $image->encode(new WebpEncoder(quality: 70)));

        return $filename;
    }

    /**
     * Store a file that is not an image (video, document) as-is on the public disk.
     */
    public function uploadFile(UploadedFile $file): string
    {
        return basename($file->storePublicly('', 'public'));
    }

    /**
     * Turn a stored media value into a value usable as an `src`. External URLs and
     * absolute paths are left untouched; bare stored names resolve to /storage/…
     */
    public function url(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    /**
     * Delete a stored file. External URLs and absolute paths are ignored.
     */
    public function delete(?string $value): void
    {
        $value = trim((string) $value);

        if ($value === '' || Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])) {
            return;
        }

        Storage::disk('public')->delete($value);
    }
}
