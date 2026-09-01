<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_seed_creates_all_frontend_pages_and_sections(): void
    {
        $this->seed();
        $this->assertDatabaseCount('pages', 10);
        $this->assertDatabaseHas('page_translations', ['locale' => 'en', 'title' => 'Home']);
        $this->assertDatabaseHas('page_sections', ['key' => 'hero']);
    }

    public function test_page_list_requires_its_permission_but_superadmin_bypasses_gate(): void
    {
        Permission::create(['name' => 'list-pages']);
        Role::create(['name' => 'superadmin']);
        $ordinary = User::factory()->create();
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->actingAs($ordinary)->get(route('cms-pages.index'))->assertForbidden();
        $this->actingAs($superadmin)->get(route('cms-pages.index'))->assertOk();
    }

    public function test_page_english_translation_and_sections_are_related(): void
    {
        $page = Page::create(['key' => 'test', 'template' => 'test', 'en' => ['title' => 'Test page']]);
        $page->sections()->create(['key' => 'hero', 'type' => 'hero', 'en' => ['title' => 'Hero title']]);

        $this->assertSame('Test page', $page->fresh()->title);
        $this->assertSame('Hero title', $page->fresh()->sections->first()->title);
    }

    public function test_section_crud_saves_visual_and_structured_options(): void
    {
        $this->seed();
        $user = User::where('email', 'superadmin@admin.com')->firstOrFail();
        $page = Page::where('key', 'home')->firstOrFail();
        $this->actingAs($user)->post(route('page-sections.store'), [
            'page_id' => $page->id,
            'key' => 'visual-test',
            'type' => 'content',
            'image' => 'images/content.jpg',
            'background_image' => 'images/background.jpg',
            'options_json' => '{"layout":"split","theme":"dark"}',
            'is_active' => 1,
            'en' => ['title' => 'Visual section'],
        ])->assertRedirect();

        $this->assertDatabaseHas('page_sections', [
            'page_id' => $page->id,
            'key' => 'visual-test',
            'image' => 'images/content.jpg',
            'background_image' => 'images/background.jpg',
        ]);
        $this->assertSame('split', $page->fresh()->sections()->where('key', 'visual-test')->firstOrFail()->options['layout']);
    }
}
