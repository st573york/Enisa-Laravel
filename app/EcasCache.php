<?php

namespace App;

use Illuminate\Support\Facades\Cache;


class EcasCache implements \Ecas\Cache\Cache {

    /**
    * Retrieves an element from the cache
    *
    * @param $key
    * @return \Ecas\Cache\CacheElement
    */
    public function get(string $key): ?\Ecas\Cache\CacheElement
    {
        return  new \Ecas\Cache\CacheElement($key, Cache::get($key),100) ;
    }

    /**
    * Stores the element in the cache
    *
    * @param \Ecas\Cache\CacheElement $element
    */
    public function set(\Ecas\Cache\CacheElement $element): bool
    {
        Cache::put($element->getKey(), $element->getValue(), $element->getTtl());
    }
    
    /**
    * Removes an element from the cache
    *
    * @param \Ecas\Cache\CacheElement $element
    * @return bool
    */
    public function delete(\Ecas\Cache\CacheElement $element): bool 
    {
        return Cache::forget($element->getKey());
    }


    /**
    * Checks if the key exists in the cache
    *
    * @param $key
    * @return bool
    */
    public function has($key): bool 
    {
        return Cache::has($key);
    }


}