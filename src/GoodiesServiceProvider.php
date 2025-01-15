<?php
/*
 * Copyright (c) 2023. Tanda Interativa - Todos os Direitos Reservados
 * Desenvolvido por Renalcio Carlos Jr.
 */

namespace ArtisanBR\Goodies;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use SplFileInfo;

class GoodiesServiceProvider extends ServiceProvider
{

    private string $config_path = __DIR__ . '/../config/';


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //Configs
        foreach (File::allFiles($this->config_path) as $file) {

            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->mergeConfigFrom(
                    $file->getPathname(), str($file->getRelativePathname())->replace('/', '.')->beforeLast('.php')->toString()
                );
            }


        }

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(collect(File::allFiles(config_path()))
                             ->mapWithKeys(fn($file): array => [
                                 $file->getPathname() => config_path($file->getRelativePathname()),
                             ])->toArray());
    }
}
