<?php

namespace Giver\Cacheable\Query;

use Illuminate\Support\Facades\Cache;

class Builder extends \Illuminate\Database\Query\Builder
{
    /**
     * The key that should be used when caching the query.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * The number of minutes to cache the query.
     *
     * @var int
     */
    protected $cacheMinutes;

    /**
     * The tags for the query cache.
     *
     * @var array
     */
    protected $cacheTags;

    /**
     * The cache driver to be used.
     *
     * @var string
     */
    protected $cacheDriver;

    /**
     * A cache prefix.
     *
     * @var string
     */
    protected $cachePrefix = 'cacheable';

    /**
     * A model class instance.
     *
     * @var string
     */
    protected $model = null;

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        if ( ! is_null($this->cacheMinutes)) {
            return $this->getCached($columns);
        }

        return parent::get($columns);
    }

    /**
     * Execute the query as a cached "select" statement.
     *
     * @param  array  $columns
     * @return array
     */
    public function getCached($columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        // If the query is requested to be cached, we will cache it using a unique key
        // for this database connection and query statement, including the bindings
        // that are used on this query, providing great convenience when caching.
        list($key, $minutes) = $this->getCacheInfo();

        $cache = $this->getCache();

        $callback = $this->getCacheCallback($columns);

        // If the "minutes" value is less than zero, we will use that as the indicator
        // that the value should be cached values should be stored indefinitely
        // and if we have minutes we will use the typical cache function here.
        if ($minutes < 0) {
            return $cache->cacheForever($key, $callback);
        }

        return $cache->cache($key, $minutes, $callback);
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return $this
     */
    public function cache($minutes, $key = null)
    {
        list($this->cacheMinutes, $this->cacheKey) = [$minutes, $key];

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @param  string  $key
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function cacheForever($key = null)
    {
        return $this->cache(-1, $key);
    }

    /**
     * Indicate that the query should not be cached.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function dontCache()
    {
        $this->cacheMinutes = $this->cacheKey = $this->cacheTags = null;

        return $this;
    }

    /**
     * Indicate that the query should not be cached. Alias for dontCache().
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function doNotCache()
    {
        return $this->dontCache();
    }

    /**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param  array|mixed  $cacheTags
     * @return $this
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache driver.
     *
     * @param  string  $cacheDriver
     * @return $this
     */
    public function cacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = Cache::driver($this->cacheDriver);

        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * Get the cache key and cache minutes as an array.
     *
     * @return array
     */
    protected function getCacheInfo()
    {
        return [$this->getCacheKey(), $this->cacheMinutes];
    }

    /**
     * Get a unique cache key for the complete query.
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cachePrefix.':'.($this->cacheKey ?: $this->generateCacheKey());
    }

    /**
     * Get the current model instance.
     *
     * @return Class
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @return string
     */
    public function generateCacheKey__()
    {
        $name = $this->connection->getName();

        return hash('sha256', $name.$this->toSql().serialize($this->getBindings()));
    }

    public function generateCacheKey($query)
    {
        $conn_name = ucfirst($this->connection->getName());
        $model_name = get_class($this->getModel());
        $query_string = $this->toSql();
        $query_params = $this->getBindings();
        $key = join('_', [$conn_name, $model_name, hash('sha256', $query_string . serialize($query_params))]);

        return $key;
    }

    /**
     * Flush the cache for the current model or a given tag name
     *
     * @param  mixed  $cacheTags
     * @return boolean
     */
    public function flushCache($cacheTags = null)
    {
        if ( ! method_exists(Cache::getStore(), 'tags')) {
            return false;
        }

        $cacheTags = $cacheTags ?: $this->cacheTags;

        Cache::tags($cacheTags)->flush();

        return true;
    }

    /**
     * Get the Closure callback used when caching queries.
     *
     * @param  array  $columns
     * @return \Closure
     */
    protected function getCacheCallback($columns)
    {
        return function () use ($columns) {
            $this->cacheMinutes = null;

            return $this->get($columns);
        };
    }

    /**
     * Set the cache prefix.
     *
     * @param string $prefix
     *
     * @return $this
     */
    public function prefix($prefix)
    {
        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * Set model
     *
     * @param Class $model
     *
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

}
