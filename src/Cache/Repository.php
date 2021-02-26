<?php

namespace RodrigoPedra\FileTaggedCache\Cache;

use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\TaggedCache;
use Illuminate\Cache\TagSet;

/**
 * @property  \RodrigoPedra\FileTaggedCache\Cache\Store $store
 */
class Repository extends TaggedCache
{
    public function __construct(Store $store, TagSet $tags)
    {
        parent::__construct($store, $tags);
    }

    protected function event($event)
    {
        if ($event instanceof KeyWritten) {
            $this->store->persistTagRelatedKeys($event->key, $event->tags);
        }

        parent::event($event);
    }

    public function getPrefix(): string
    {
        return '';
    }
}
