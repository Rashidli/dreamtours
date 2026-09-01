<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\GalleryMedia;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GalleryMediaAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_can_be_filtered_edited_updated_and_deleted(): void
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        $this->actingAs($user);

        $album = GalleryAlbum::create(['is_active' => true, 'en' => ['title' => 'Summer Album']]);
        $medium = $album->media()->create([
            'type' => 'image',
            'path' => 'original.jpg',
            'en' => ['caption' => 'Original caption'],
        ]);

        $this->get(route('gallery-media.index', [$album, 'q' => 'Original', 'type' => 'image']))
            ->assertOk()->assertSee('original.jpg')->assertSee('Original caption');
        $this->get(route('gallery-media.edit', [$album, $medium]))->assertOk()->assertSee('original.jpg');
        $this->put(route('gallery-media.update', [$album, $medium]), [
            'type' => 'video',
            'path' => 'https://example.com/video',
            'thumbnail' => 'thumb.jpg',
            'sort_order' => 4,
            'en' => ['alt_text' => 'Video', 'caption' => 'Updated caption'],
        ])->assertRedirect(route('gallery-media.index', $album));

        $this->assertDatabaseHas('gallery_media', ['id' => $medium->id, 'type' => 'video', 'sort_order' => 4]);
        $this->assertDatabaseHas('gallery_media_translations', ['gallery_media_id' => $medium->id, 'caption' => 'Updated caption']);
        $this->delete(route('gallery-media.destroy', [$album, $medium]))->assertRedirect();
        $this->assertDatabaseMissing('gallery_media', ['id' => $medium->id]);
    }
}
