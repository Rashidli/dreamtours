<?php

namespace Tests\Feature;

use Tests\TestCase;

class DreamTourInstallationTest extends TestCase
{
    public function test_front_pages_are_available(): void
    {
        $pages = [
            '/', '/about', '/contact', '/cruises', '/gallery',
            '/international-tours', '/multi-day-tours', '/one-day-tours',
            '/private-tours', '/visa-support',
        ];

        foreach ($pages as $page) {
            $this->get($page)
                ->assertOk()
                ->assertSee('DreamTour');
        }
    }

    public function test_admin_login_is_available(): void
    {
        $this->get('/admin')->assertOk();
    }

    public function test_admin_pages_require_authentication(): void
    {
        $this->get('/admin/users')->assertRedirect('/admin');
    }
}
