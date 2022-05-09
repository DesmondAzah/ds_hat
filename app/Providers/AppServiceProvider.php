<?php

namespace App\Providers;

use App\Repository\HatLevelRankRepository;
use App\Repository\HatLevelRepository;
use App\Repository\HatPcrRepository;
use App\Repository\HatRankRepository;
use App\Repository\HatRepository;
use App\Repository\PersonnelHatRepository;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton("HatRepository", HatRepository::class);
        $this->app->singleton("HatLevelRepository", HatLevelRepository::class);
        $this->app->singleton("HatRankRepository", HatRankRepository::class);
        $this->app->singleton("HatLevelRankRepository", HatLevelRankRepository::class);
        $this->app->singleton("HatPcrRepository", HatPcrRepository::class);
        $this->app->singleton("PersonnelHatRepository", PersonnelHatRepository::class);
        $this->app->singleton("ExcelServiceProvider", ExcelServiceProvider::class);
    }
}
