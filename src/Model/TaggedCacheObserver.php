<?php

namespace RodrigoPedra\FileTaggedCache\Model;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use RodrigoPedra\FileTaggedCache\Contracts\CacheTaggable;

class TaggedCacheObserver
{
    protected Repository $cache;

    public function __construct(Factory $cacheManager, ?string $store = null)
    {
        $this->cache = $cacheManager->store($store);
    }

    public function created(CacheTaggable $model): void
    {
        $this->cache->tags([$model])->flush();
    }

    public function updated(CacheTaggable $model): void
    {
        $this->cache->tags([$model])->flush();
    }

    public function deleted(CacheTaggable $model): void
    {
        $this->cache->tags([$model])->flush();
    }

    public function restored(CacheTaggable $model): void
    {
        $this->cache->tags([$model])->flush();
    }

    public function forceDeleted(CacheTaggable $model): void
    {
        $this->cache->tags([$model])->flush();
    }
}
