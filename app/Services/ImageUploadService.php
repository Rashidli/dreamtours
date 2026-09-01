<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageUploadService
{
    public function upload($file): string
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->read($file);
        $image = $image->toWebp(60);

        $filename = Str::uuid() . '.webp';
        Storage::put('public/' . $filename, (string) $image);
        return $filename;
    }
}
