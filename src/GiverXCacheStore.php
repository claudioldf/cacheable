<?php
namespace Giver\Cacheable;
#namespace Illuminate\Cache;

#use Illuminate\Contracts\Cache\Store;

class GiverXCacheStore extends \Illuminate\Cache\TaggableStore implements \Illuminate\Contracts\Cache\Store
{
    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new WinCache store.
     *
     * @param  string  $prefix
     * @return void
     */
    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $value = xcache_get($this->prefix.$key);

        if (isset($value)) {
            return unserialize($value); //Alterado por Fábio, o XCache não suporta objeto
        }
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        xcache_set($this->prefix.$key, serialize($value), $minutes * 60); //Alterado por Fábio, o XCache não suporta objeto
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        return xcache_inc($this->prefix.$key, $value);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return xcache_dec($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return xcache_unset($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        xcache_clear_cache(XC_TYPE_VAR);
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    
    public function deleteByPrefix($prefix)
    {
        $count = 0;
        $keys = GiverXCacheStore::_getCacheKeys();

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (strpos($key, $prefix) === 0) {
                    $count++;
                    GiverXCacheStore::delete($key);
                }
            }
        }
        return $count;
    }

    protected function _getCacheKeys()
    {
        GiverXCacheStore::checkAuth();
        $keys = array();
        for ($i = 0, $count = xcache_count(XC_TYPE_VAR); $i < $count; $i++) {
            $entries = xcache_list(XC_TYPE_VAR, $i);
            if (is_array($entries['cache_list'])) {
                foreach ($entries['cache_list'] as $entry) {
                    $keys[] = $entry['name'];
                }
            }
        }

        return $keys;
    }

    protected function checkAuth()
    {
        if (ini_get('xcache.admin.enable_auth'))
        {
            echo 'To use all features of the "GiverXCacheStore" class, you must set "xcache.admin.enable_auth" to "Off" in your php.ini.';
            die();
        }
    }

    protected function _getKey($id)
    {
        $prefix = isset($this->prefix) ? $this->prefix : '';

        if ( ! $prefix || strpos($id, $prefix) === 0) {
            return $id;
        } else {
            return $prefix . $id;
        }
    }

    public function delete($id)
    {
        $key = GiverXCacheStore::_getKey($id);

        if (strpos($key, '*') !== false) {
            return GiverXCacheStore::deleteByRegex('/' . str_replace('*', '.*', $key) . '/');
        }

        return GiverXCacheStore::_doDelete($key);
    }

    public function deleteByRegex($regex)
    {
        $count = 0;
        $keys = GiverXCacheStore::_getCacheKeys();

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (preg_match($regex, $key)) {
                    $count++;
                    $this->delete($key);
                }
            }
        }
        return $count;
    }

    protected function _doDelete($id)
    {
        return xcache_unset($id);
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys) {
        $data = [];

        foreach($keys as $key) {
            $data[$this->prefix.$key] = $this->get($key);
        }

        return $data;
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes) {
        foreach($values as $key => $value) {
            $this->put($key, $value, $minutes);
        }
    }
}
