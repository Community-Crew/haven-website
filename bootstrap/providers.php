<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ScrambleServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ScrambleServiceProvider::class,
];
