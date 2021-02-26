<?php

namespace RodrigoPedra\FileTaggedCache\Cache;

use Illuminate\Cache\FileStore;

class Store extends FileStore
{
    public function tags($names): Repository
    {
        return new Repository($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
    }

    public function persistTagRelatedKeys(string $key, array $tags)
    {
        if (count($tags) === 0) {
            return;
        }

        $payload = serialize([
            'key' => $key,
            'tags' => $tags,
        ]);

        foreach ($tags as $tag) {
            $metaKey = $this->tagMetaKey($tag);

            $values = $this->get($metaKey) ?? [];
            $values = is_array($values) ? $values : [];

            $values[] = $payload;

            $this->forever($metaKey, array_unique($values));
        }
    }

    public function flushTagRelatedKeys(string $tag)
    {
        $metaKey = $this->tagMetaKey($tag);

        $values = $this->get($metaKey) ?? [];
        $values = is_array($values) ? $values : [];

        foreach ($values as $value) {
            $this->forgetRelatedKey($value);
        }

        $this->forget($metaKey);
    }

    protected function forgetRelatedKey(string $value)
    {
        $payload = unserialize($value);

        if (! is_array($payload)) {
            return;
        }

        $key = $payload['key'] ?? null;
        $tags = $payload['tags'] ?? [];

        if (is_string($key) && is_array($tags)) {
            $this->tags($tags)->forget($key);
        }
    }

    protected function tagMetaKey(string $tag): string
    {
        return 'file-tag:' . $tag . ':key';
    }
}
