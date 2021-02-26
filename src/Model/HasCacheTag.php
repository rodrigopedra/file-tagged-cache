<?php

namespace RodrigoPedra\FileTaggedCache\Model;

use Illuminate\Support\Str;

trait HasCacheTag
{
    public function cacheTagKey(): string
    {
        return Str::slug($this->getMorphClass() . '-' . $this->getKey());
    }
}
