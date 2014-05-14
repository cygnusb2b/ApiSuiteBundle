<?php
namespace Cygnus\ApiSuiteBundle\Traits;

use Snc\RedisBundle\Client\Phpredis\Client as CacheClient;
use Symfony\Component\HttpFoundation\Request;

trait CacheTraitRedis
{
    /**
     * The Redis cache client
     *
     * @var  Snc\RedisBundle\Client\Phpredis\Client $cacheClient
     */
    protected $cacheClient;

    /**
     * Sets the Redis cache client
     *
     * @param  Snc\RedisBundle\Client\Phpredis\Client $cacheClient
     * @return self
     */
    public function setCacheClient(CacheClient $cacheClient)
    {
        $this->cacheClient = $cacheClient;
        return $this;
    }

    /**
     * Creates a cache key from the request
     *
     * @param  Request $request The API request
     * @return string The cache key
     */
    public function generateCacheKey(Request $request)
    {
        $key = sprintf(
            '%s:%s:%s:%s', 
            basename(strtr(get_class($this), '\\', '/')), 
            $request->getHost(),
            $request->getMethod(),
            $request->getPathInfo()
        );
        $qs = $request->getQueryString();
        if (!empty($qs)) {
            $key .= sprintf(':%s', $qs);
        }
        return $key;
    }

    /**
     * Determines if an object exists in cache
     *
     * @param  string $cacheKey The cache key to check
     * @return bool   Whether the object exists in cache
     */
    public function hasCache($cacheKey)
    {
        return $this->cacheClient->exists($cacheKey);
    }

    /**
     * Gets an object from cache
     *
     * @param  string $cacheKey The cache key to retrieve the cache value from
     * @return mixed
     */
    public function getCache($cacheKey)
    {
        if ($this->hasCache($cacheKey)) {
            $value = $this->cacheClient->get($cacheKey);
            return is_numeric($value) ? $value : unserialize($value);
        }
        return null;
    }

    /**
     * Adds an object to cache
     *
     * @param  string $cacheKey The cache key to set the cache value to
     * @param  mixed  $value    The value to set
     * @param  int    $expire   The number of seconds until this key expires. A value of zero will cache indefinitely
     * @return self
     */
    public function setCache($cacheKey, $value, $expire = 0)
    {
        $expire = (int) $expire;
        $value = is_numeric($value) ? $value : serialize($value);
        $this->cacheClient->set($cacheKey, $value);

        if ($expire > 0) {
            // Add the expiration
            $this->cacheClient->expire($expire);
        }
        return $this;
    }
}
