<?php

namespace CyberDuck\CacheingMemoizer;

class CacheingMemoizer {

    protected static $instance = null;
    protected static $cacheDuration = 300;
    protected $memoizeKeys = [];
    protected $shutdownFunctions = [];

    use MemoizesResults {
        memoize as traitMemoize;
    }
    protected $cacheRetrievalFunction = null;

    private function __construct(){
        $this->cacheRetrievalFunction = function () {
            return false;
        };
    }

    /**
     * Add function to be called at the end of request to
     * cache memoized values
     *
     * signature should be ($key, $value)
     *
     * @param callable $function
     */
    public static function addShutdownFunction($function)
    {
        self::getInstance()->shutdownFunctions[] = $function;
    }

    /**
     * Retrieve singleton instance of SimpleMemoizer
     *
     *@return \CyberDuck\CacheingMemoizer\CacheingMemoizer
     */

    public static function getInstance(){
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set function to retrieve value from cache (if it's there)
     *
     * @param $function
     */

    public static function setCacheRetrievalFunction($function)
    {
        self::getInstance()->cacheRetrievalFunction = $function;
    }

    /**
     * Should be called at end of request by framework. Effect
     * will be to call the shutdown functions added with
     * addShutdownFunction
     */

    public static function shutdown()
    {
        self::getInstance()->doShutdown();
    }

    protected function doShutdown()
    {
        array_walk($this->shutdownFunctions, function($function){

            foreach($this->memoizeKeys as $key => $value) {

                $function($key, $this->memoizedStuff[$key]);
            }
        });

    }

    /**
     * Convenience method for sharing cache duration amongst
     * different cache systems
     *
     * @return int
     */

    public static function getCacheDuration()
    {
        return self::$cacheDuration;
    }

    /**
     * Convenience method for sharing cache duration amongst
     * different cache systems
     *
     * @return int
     */


    public static function setCacheDuration($duration)
    {
        self::$cacheDuration = $duration;
    }

    /**
     * Memoize result globally with retrieval and storage
     * in cache
     *
     * @param       $key
     * @param       $function
     * @param array ...$params
     *
     * @return mixed
     */
    public function memoizeWithCache($key, $function, ...$params)
    {
        return $this->memoize($key, function($key, $function, $params) {
            if ($value = call_user_func($this->cacheRetrievalFunction, $key)) {
                return $value;
            }

            //only set memo key here so it's not continually refreshed
            $this->memoizeKeys[$this->getMemoizeKey($key)] = $this->getMemoizeKey($key);

            return $function(...$params);

        }, $key, $function, $params);
    }

    /**
     * See trait documentation. This adds public access
     * to protected method.
     */
    public function memoize($key, $function, ...$params)
    {
        return $this->traitMemoize($key, $function, ...$params);
    }

    protected function getCacheKey($key) {
        return 'saved-memo-' . md5($key);
    }


}