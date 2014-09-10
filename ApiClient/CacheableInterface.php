<?php
namespace Cygnus\ApiSuiteBundle\ApiClient;

use Symfony\Component\HttpFoundation\Request;

interface CacheableInterface
{
    /**
     * Sets the cache client
     *
     * @param  $cacheClient
     * @return self
     */
    public function setCacheClient($cacheClient);

    /**
     * Creates a cache key from the Request object
     *
     * @param  Request $request The API request
     * @return string The cache key
     */
    public function generateCacheKey(Request $request);

    /**
     * Determines if an object exists in cache
     *
     * @param  string $cacheKey The cache key to check
     * @return bool   Whether the object exists in cache
     */
    public function hasCache($cacheKey);

    /**
     * Gets an object from cache
     *
     * @param  string $cacheKey The cache key to retrieve the cache value from
     * @return mixed
     */
    public function getCache($cacheKey);

    /**
     * Adds an object to cache
     *
     * @param  string $cacheKey The cache key to set the cache value to
     * @param  mixed  $value    The value to set
     * @param  int    $expire   The number of seconds until this key expires. A value of zero will cache indefinitely
     * @return self
     */
    public function setCache($cacheKey, $value, $expire = 0);
}
