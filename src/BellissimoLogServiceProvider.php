<?php

use Illuminate\Support\ServiceProvider;

class BellissimoLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/belissimo-log.php', 'belissimo-log'
        );
    }
}