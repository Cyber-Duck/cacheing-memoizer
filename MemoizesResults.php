<?php

namespace CyberDuck\CacheingMemoizer;

trait MemoizesResults
{

    private static $simpleMemoizer = null;
    protected $memoizedStuff = [];
    protected $disableMemoization = false;

    /**
     * Forget memoized results
     *
     * Forget a specific memoized result by setting arg
     *
     * @param null $onlyMe
     *
     * @return $this
     */
    public function forget($onlyMe = null)
    {
        if ($onlyMe !== null) {
            unset($this->memoizedStuff[$this->getMemoizeKey($onlyMe)]);
        } else {
            $this->memoizedStuff = [];
        }

        return $this;
    }

    /**
     * Memoize the return value of a function with specified key in this
     * instance of the object
     *
     * @param       $key
     * @param       $function
     * @param array ...$params
     *
     * @return mixed
     */

    protected function memoize($key, $function, ...$params)
    {

        $key = $this->getMemoizeKey($key);

        if ($this->disableMemoization || !array_key_exists($key, $this->memoizedStuff)) {
            $this->memoizedStuff[$key] = $function(... $params);
        }

        return $this->memoizedStuff[$key];
    }

    /**
     *
     * Retrieve a key suitable for using as an array index
     * (i.e. a string) from
     * $key.
     *
     * This is one simple approach but you may wish to override
     * for your use case.
     *
     * @param $key
     *
     * @return string
     */

    protected function getMemoizeKey($key)
    {
        if (is_array($key)) {
            return md5(serialize(array_map([$this, 'getMemoizeKey'], $key)));
        } else {
            if (is_object($key)) {
                return spl_object_hash($key);
            } else {
                return "$key";
            }
        }
    }

    /**
     * Globally memoize the return value of a function as with globallyMemoize,
     * but also cache this result (and load the cached result if possible on
     * subsequent requests)
     *
     * @param       $key
     * @param       $function
     * @param array ...$params
     *
     * @return mixed
     */

    protected function globallyMemoizeWithCache($key, $function, ...$params)
    {
        return self::getSimpleMemoizer()->memoizeWithCache($this->getMemoizeKey($key), $function, ...$params);
    }

    private static function getSimpleMemoizer()
    {

        if (!self::$simpleMemoizer) {
            self::$simpleMemoizer = CacheingMemoizer::getInstance();
        }

        return self::$simpleMemoizer;
    }

    /**
     * Memoize the return result of a function for every instance of this specific
     * class
     *
     * @param       $key
     * @param       $function
     * @param array ...$params
     *
     * @return mixed
     */

    protected function singletonMemoize($key, $function, ...$params)
    {
        $singleton = $this->globallyMemoize(get_class($this) . 'singleton',
            function () {
                return $this;
            });

        return $singleton->memoize($key, $function, ...$params);
    }

    /**
     * Memoize the return value of a function with specified key globally
     *
     * @param       $key
     * @param       $function
     * @param array ...$params
     *
     * @return mixed
     */
    protected function globallyMemoize($key, $function, ...$params)
    {
        return self::getSimpleMemoizer()->memoize($this->getMemoizeKey($key), $function, ...$params);
    }


}

