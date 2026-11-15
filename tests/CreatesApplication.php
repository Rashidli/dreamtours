<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // RefreshDatabase wipes whatever database it connects to. A cached config
        // (php artisan optimize) ignores phpunit.xml, so refuse to run against
        // anything but the dedicated test database.
        $database = $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');
        if ($database !== 'websites_tours_test' || $app->configurationIsCached()) {
            fwrite(STDERR, "Refusing to run tests against database [{$database}]. Run `php artisan config:clear` first.\n");
            exit(1);
        }

        return $app;
    }
}
