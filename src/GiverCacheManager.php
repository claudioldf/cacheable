<?php
namespace Giver\Cacheable;

class GiverCacheManager extends \Illuminate\Cache\CacheManager
{
    protected function createXcacheDriver(array $config)
    {
        return $this->repository(new GiverXCacheStore($this->getPrefix($config)));
    }
}
