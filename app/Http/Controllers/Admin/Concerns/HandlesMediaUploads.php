<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Services\ImageUploadService;
use Illuminate\Http\Request;

/**
 * Shared upload handling for the CMS modules. Every media field is offered twice
 * in the admin forms: a file input (`<field>`) and a URL/path fallback
 * (`<field>_path`), so editors can either upload or point at an external image.
 */
trait HandlesMediaUploads
{
    protected function media(): ImageUploadService
    {
        return app(ImageUploadService::class);
    }

    /**
     * Validation rules for one media field.
     *
     * @return array<string, array<int, string>>
     */
    protected function mediaRules(string $field, int $maxKilobytes = 4096): array
    {
        return [
            $field => ['nullable', 'image', 'max:'.$maxKilobytes],
            $field.'_path' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Resolve one media field into the value that should be stored: a freshly
     * uploaded file wins, then a pasted URL/path, then the existing value.
     *
     * @param  array<string, mixed>  $data  validated data, cleaned up in place
     */
    protected function resolveMedia(Request $request, array &$data, string $field, ?string $current = null): void
    {
        unset($data[$field.'_path']);

        if ($request->hasFile($field)) {
            $data[$field] = $this->media()->upload($request->file($field));

            return;
        }

        if ($request->filled($field.'_path')) {
            $data[$field] = trim((string) $request->input($field.'_path'));

            return;
        }

        $data[$field] = $current;
    }

    /**
     * Resolve a repeatable media field: newly uploaded files are appended to the
     * lines kept in the `<field>_text` textarea.
     *
     * @param  array<string, mixed>  $data  validated data, cleaned up in place
     */
    protected function resolveMediaList(Request $request, array &$data, string $field): void
    {
        unset($data[$field.'_text']);

        $existing = collect(preg_split('/\r\n|\r|\n/', (string) $request->input($field.'_text', '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $uploaded = collect($request->file($field, []))
            ->filter()
            ->map(fn ($file) => $this->media()->upload($file));

        $data[$field] = $existing->concat($uploaded)->values()->all();
    }
}
