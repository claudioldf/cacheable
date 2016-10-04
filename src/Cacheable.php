<?php

namespace Giver\Cacheable;

use Giver\Cacheable\Query\Builder;

trait Cacheable
{
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        $builder = new Builder($conn, $grammar, $conn->getPostProcessor());
        $builder->setModel($this);

        if (isset($this->cacheFor)) {
            $builder->cache($this->cacheFor);
        }

        if (isset($this->cacheCacheTag)) {
            $builder->cacheTags($this->cacheCacheTag);
        }

        if (isset($this->cacheCachePrefix)) {
            $builder->prefix($this->cacheCachePrefix);
        }

        return $builder;
    }
}
