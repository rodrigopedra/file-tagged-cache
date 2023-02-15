<?php

namespace RodrigoPedra\FileTaggedCache;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RodrigoPedra\FileTaggedCache\Cache\Store;

class FileTaggedCacheServiceProvider extends ServiceProvider
{
    public function boot(CacheManager $manager): void
    {
        $manager->extend('file-tagged', function (Application $app, array $config) use ($manager) {
            $store = new Store($app['files'], $config['path'], $config['permission'] ?? null);

            return $manager->repository($store);
        });
    }
}
