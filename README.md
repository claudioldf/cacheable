Cacheable, Laravel 5 query cache
===================================

Cacheable is an Eloquent trait for Laravel 5.0+ that brings back the `cache()` query functions from Laravel 4.2. This makes it super easy to cache your query results for an adjustable amount of time.

    // Get a the first user's posts and cache them for a day.
    User::first()->cache(1440)->posts()->get();

It works by simply cacheing the SQL query that was used and storing the result. If the same query is attempted while the cache is persisted it will be retrieved from the store instead of hitting your database again.

## Installation

Install using Composer, just as you would anything else.

    composer require giver/cacheable

The easiest way to get started with Eloquent is to create an abstract `App\Model` which you can extend your application models from. In this base model you can import the cacheable trait which will extend the same caching functionality to any queries you build off your model.

    <?php
    namespace App;

    use Giver\Cacheable\Cacheable;
    use Illuminate\Database\Eloquent\Model as Eloquent;

    abstract class Model extends Eloquent
    {
        use Cacheable;
    }

Now, just ensure that your application models from this new `App\Model` instead of Eloquent.

    <?php
    namespace App;

    class Post extends Model
    {
        //
    }

Alternatively, you can simply apply the trait to each and every model you wish to use `cache()` on.

## Usage

Using the cache method is super simple. Just pass the number of minutes you want to store the result of that query in the cache for, and whenever the same query is called within that time frame the result will be pulled from the cache, rather than from the database again.

    // Cache the number of users for an hour.
    $users = User::cache(60)->count();

### Cache tags

If you want to tag certain queries you can add `cacheTags('tag_name')` to your query. Please notice that cache tags are not supported by all cache drivers.

    // Cache the number of users for an hour and tag it with 'user_queries'
    User::cache(60)->cacheTags('user_queries')->count();

### Cache prefix

If you want a unique prefix added to the cache key for each of your queries (say, if your cache doesn't support tagging), you can add `prefix('prefix')` to your query.

    // Cache the number of users for an hour and prefix the key with 'users'
    User::cache(60)->prefix('users')->count();

Alternatively, you can add the ``$cacheCachePrefix` property to your model to always use that cache prefix.

#### Model wide cache tag

You can set a cache tag for all queries of a model by setting the `$cacheCacheTag` property with an unique string that should be used to tag the queries.

### Relationships

Validating works by caching queries on a query-by-query basis. This means that when you perform eager-loading those additional queries will not be cached as well unless explicitly specified. You can do that by using a callback with your eager-loads.

    $users = User::where("id", ">", "1")
        ->with(['posts' => function ($q) { $q->cache(10); }])
        ->cache(10)
        ->take(5)
        ->get();

### Always enable

You can opt-in to cache all queries of a model by setting the `$cacheFor` property with the number of minutes you want to cache results for. Use this feature with caution as it could lead to unexpected behaviour and stale data in your app if you're not familiar with how it works.

### Cache flushing

Based on the architecture of the package it's not possible to delete the cache for a single query. But if you tagged any queries using cache tags, you are able to flush the cache for the tag:

    User::flushCache('user_queries');

If you used the `$cacheCacheTag` property you can use the method without a parameter and the caches for the tag set by `$cacheCacheTag` are flushed:

    User::flushCache();
