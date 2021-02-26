# File Tagged Cache Driver for Laravel

This package provides support for Tagged Cache using the file driver for Laravel

## Installation

```
composer require rodrigopedra/file-tagged-cache
```

## Configuration

After installation, you can configure a new cache store in your project's `./config/cache.php` file and
use `file-tagged` as the cache driver.

```php
// ./config/cache.php

'stores' => [
    // ...

    'posts' => [
        'driver' => 'file-tagged', // custom driver added by this package
        'path' => storage_path('framework/cache/posts'),
    ],
],
```

### Storage folder

If you are using a custom directory to store you cache data, it is a good idea to create that directory beforehand 
and list it in the relevant `.gitignore` files.

For example, the snippet above configures the cache data to be stored inside the `./storage/framework/cache/posts`
directory.

As this directory does not exist in your default Laravel installation, you need to create it.

For example:

```text
./storage
├── app
├── framework
│   ├── cache
│   │   ├── data
│   │   │   └── .gitignore
│   │   ├── posts
│   │   │   └── .gitignore
│   │   └── .gitignore
│   ├── .gitignore
│   ├── sessions
│   ├── testing
│   └── views
└── logs
```

We added a new `/posts` directory inside the `./storage/framework/cache/` directory.

The `.gitgnore` file inside `./storage/framework/cache/data` should be kept the same as shipped by Laravel.

The `.gitgnore` file inside `./storage/framework/cache/posts` should be configured as below:

~~~gitignore
*
!.gitignore
~~~

The `.gitignore` file inside `./storage/framework/cache` (parent directory of both `/data` and `/posts`)
should be changed to allow the created `/posts` directory, as below:

~~~gitignore
*
!data/
!posts/
!.gitignore
~~~

## Usage

You can use the `Cache` façade to get an instance of this custom store and use it as 
any other Tagged Cache store shipped with Laravel.

```php
use Illuminate\Support\Facades\Cache;

Cache::store('posts')->tags(['a-tag', 'another-tag'])->put('key', 'Hello World!');
```

If you are not familiar with how Tagged Cache works in Laravel, take a look in 
the related Laravel documentation about it:

https://laravel.com/docs/8.x/cache#cache-tags

## Motivation

This package was initially built to provide an easy way to cache views that are dependent 
on Eloquent models in servers  where a more adequate tagged cache solution is unavailable.

Be aware this cache driver is aimed to be used in projects where content doesn't change very frequently 
and changes are made by few users (ideally one user) at a time.

If your project has a more dynamic nature, or allows many users to edit the same content at the same time,
consider using one of the Tagged Cache drivers shipped with Laravel.

### Usage With Eloquent Models

***This section is entirely optional, the resources provided here do not interfere with the cache driver usage.***

This package provides an Observer, a Contract, and a Trait to make usage with Eloquent Models easier.

Let's consider a Blog project with 3 models: `Post`, `Author` and `Comment`.

We could add the Observer and Trait to the models as such:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RodrigoPedra\FileTaggedCache\Model\CacheTaggable;
use RodrigoPedra\FileTaggedCache\Model\HasCacheTag;
use RodrigoPedra\FileTaggedCache\Model\TaggedCacheObserver;

class Post extends Model implements CacheTaggable
{
    use HasCacheTag;
    
    public function author() {
        return $this->belongsTo(Author::class);
    }
    
    public function comments() {
        return $this->hasMany(Comment::class);
    }
    
    public static function booted() {
        static::observe(TaggedCacheObserver::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RodrigoPedra\FileTaggedCache\Model\CacheTaggable;
use RodrigoPedra\FileTaggedCache\Model\HasCacheTag;
use RodrigoPedra\FileTaggedCache\Model\TaggedCacheObserver;

class Author extends Model implements CacheTaggable
{
    use HasCacheTag;
    
    public function posts() {
        return $this->hasMany(Post::class);
    }
    
    public static function booted() {
        static::observe(TaggedCacheObserver::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $touches = [
        'post',
    ];
    
    public function post() {
        return $this->belongsTo(Post::class);
    }
}
```

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RodrigoPedra\FileTaggedCache\Model\TaggedCacheObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(TaggedCacheObserver::class)->needs('$store')->give('posts');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
```

The `CacheTaggable` contract requires just one method to be implemented: `cacheTagKey()`
which should return a string representing the key that should be used when this model is used as a cache tag.

The `HasCacheTag` implement this method with a sensible default. You are not required to use this trait, 
and can provide your custom implementation to the `cacheTagKey()` method.

The `TaggedCacheObserver` add listeners that will flush the cache items tagged by these models keys 
when model events that change content are dispatched.

The `Comment` model does not use any of the helpers Observer, Contract, and Trait. But it is configured 
to *"touch"* its parent `Post` model, to invalidate its parent `Post` related cached items when 
a comment is created or changed.

If you are not familiar with how `$touch` works, take a look in the related Laravel documentation about it:

https://laravel.com/docs/8.x/eloquent-relationships#touching-parent-timestamps

Finally, but important, in your project's `AppServiceProvider` you'll need to configure 
Laravel's Service Container to provide your custom store identifier to the `TaggedCacheObserver`.

**IMPORTANT**: The observer will only trigger cache invalidation when related models' data are manipulated
through standard Eloquent methods. If you use the `DB` façade, or edit data directly in the database, 
you'll need to invalidate the cache manually using the same methods provided by Laravel for any cache store. 

### Usage in Controllers

This is an example of how you could use the models configured above to cache a view:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PostsController extends Controller
{
    public function index(Request $request, Post $post)
    {
        $cacheKey = Str::slug($request->path());

        return Cache::store('posts')
            ->tags([$post, $post->author]) // tagging by post and author
            ->sear($cacheKey, function () use ($post) {
                // IMPORTANT: call ->render() to cache the rendered view string
                return view('posts.show', [
                    'post' => $post,
                    'author' => $post->author,
                    'comments' => $post->comments,
                ])->render();
            });
    }
}
```

In this example, the rendered view is cached and on a next visit the cached content will 
be served instead of rendering it again.

Also, as `$post->comments` is run only inside the closure, this query will only be performed 
when the cached item cannot be found.

As in this example we tagged the cache item with both `$post` and `$post->author`, whenever one 
of these models changes, this cached view will be deleted from the cache store.

And as we configured the `Comment` model to *"touch"* its parent `Post` model, when a comment is added, 
updated, or deleted, this cached view will also be deleted from the cache store.

### Usage with Laravel Mix

If you use Laravel Mix to compile your frontend assets, and use assets versioning, you might 
end up with stale views after changing your frontend assets.

To mitigate that you can add a *"after"* callback in your `webpack.mix.js` script, to clear 
the cache after compiling your assets:

~~~js
let {exec} = require('child_process');
let mix = require('laravel-mix');

// ... other mix config

// If you are using Laravel Mix 6, mix.then() is an alias to mix.after()
// "posts" is the custom cache stored we configured above
mix.then(() => exec('php artisan cache:clear posts'));
~~~

## To-Do

- Add tests
- Consider an easier way to provide the `$store` to the `TaggedCacheObserver`

## License

This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
