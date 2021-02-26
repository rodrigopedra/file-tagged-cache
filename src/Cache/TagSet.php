<?php

namespace RodrigoPedra\FileTaggedCache\Cache;

use Illuminate\Cache\TagSet as BaseTagSet;
use Illuminate\Contracts\Cache\Store;
use RodrigoPedra\FileTaggedCache\Contracts\CacheTaggable;

/**
 * @property  \RodrigoPedra\FileTaggedCache\Cache\Store $store
 */
class TagSet extends BaseTagSet
{
    public function __construct(Store $store, array $names = [])
    {
        $names = \array_map(function ($name) {
            if (\is_object($name) && $name instanceof CacheTaggable) {
                return $name->cacheTagKey();
            }

            return $name;
        }, $names);

        parent::__construct($store, $names);
    }

    public function resetTag($name): string
    {
        $this->store->flushTagRelatedKeys($name);

        return parent::resetTag($name);
    }
}
