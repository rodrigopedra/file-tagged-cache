<?php

namespace RodrigoPedra\FileTaggedCache\Model;

interface CacheTaggable
{
    public function cacheTagKey(): string;
}
