<?php

namespace RodrigoPedra\FileTaggedCache\Contracts;

interface CacheTaggable
{
    public function cacheTagKey(): string;
}
