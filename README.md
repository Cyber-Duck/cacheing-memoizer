# 🚨 Discontinued 🚨
Please consider using the out of the box caching functionality in Laravel.


# Simple Cacheing Memoizer

Our Simple Cacheing Memoizer is a flexible memoizer that can also optionally be combined with a cache to automatically 
memoize results of calling functions. 
  
We've implemented one example of the latter function using cyber-duck/laravel-cacheing-memoizer which adds it to Laravel for you, but
it can also be used in other frameworks - it was originally written for a legacy site that is being upgraded from Codeigniter to
Laravel gradually, and works fine in both. 

## Usage

### Memoization

add  

`use CyberDuck\CacheingMemoizer\MemoizesResults`

to your class. 

This will give your class extra methods, the most important of which is `memoize`.

Memoize takes two or more arguments. 

The first are the building blocks of the memoization key.
The second is a function that will return the value to be memoized.
The third and any subsequent arguments will be passed to the function when it is invoked.

Memoization is by default unique to the object, but it is also possible to use `singletonMemoize` 
which is exactly the same as `memoize` except it memoizes the value of the result in one sningleton

Important note: Because this is framework agnostic, the memoizer has to manage the singleton itself. So it may not be the
'same' singleton as your framework preservers. If this is important then over-write the `singletonMemoize` method.

There is also a `globallyMemoize` method. This essentially memoizes the result with that key for any object.

### Cacheing

You may also use the memoization features with automated cacheing. This requires two steps:

1.  Setup the memoizer to use the cache by:

    1.  call `CyberDuck\CacheingMemoizer\CacheingMemoizer::setCacheRetrievalFunction` with a _callable_ which should accept one
argument - the cache key - and return the result if there is a cache hit, null other wise. 
    2. call `CyberDuck\CacheingMemoizer\CacheingMemoizer::addShutdownFunction` at least once with a _callable_ which should accept
two arguments - key, then value, which will write to the cache
    3. arrange for `CyberDuck\CacheingMemoizer\CacheingMemoizer::shutdown` to be called at the end of the request.

(if you are using Laravel, `cyber-duck/laravel-cacheing-memoizer` will do this for you)
      
2.  Use `globallyMemoizeWithCache` in the same way as `globallyMemoize`

### Other methods

See source code. Documentation coming. `forget` and `getMemoizeKey` are the main ones, their import hopefully obvious.


###Can I see a couple of examples of this in use please?

Certainly, here's a simple example of using it in one class to avoid the overhead of an expensive call:

```

    public function getPackageAttribute()
    {
        return $this->memoize('package',
            function () {
                return package()->create(['code' => $this->PackageTypeCode]);
            });
    }


```

Here's an example of how quickly we added memoization + cacheing to a function in 5+ year old legacy code, without
having to rewrite anything fresh. The original function looked like:

```
    public function find_all($params = array(), $skip = array('page', 'limit', 'sort-by'))
    {
        $this->db->select('waOrg.OrgID, waOrg.OrgName, waOrg.PackageTypeCode, waOrg.HasGallery, waOrg.HasShowReel, waOrg.AllowSearchResultsPageLogo');
        $this->db->join($this->dbn.'[wlEntry]', 'wlEntry.OrgID = waOrg.OrgID');
        $this->db->join($this->dbn_live.'[wpCountry]', 'wpCountry.Country = waOrg.Country', 'left');
        $this->db->group_by('waOrg.OrgID, waOrg.OrgName, waOrg.PackageTypeCode, waOrg.HasGallery, waOrg.HasShowReel, waOrg.AllowSearchResultsPageLogo');
        $this->db->distinct();

        $this->_apply_filters($params, $skip);

        $query = $this->db->get($this->dbn.'[waOrg] waOrg');

        if(count($query->result()) > 0)
        {
            return count($query->result());
        }

        return false;
    }

```


With automated memoization + cacheing it now looks like:

```

    public function find_all($params = array(), $skip = array('page', 'limit', 'sort-by'))
    {
        return $this->globallyMemoizeWithCache(['org.find.all', $params, $skip], function($params, $skip){
            $this->db->select('waOrg.OrgID, waOrg.OrgName, waOrg.PackageTypeCode, waOrg.HasGallery, waOrg.HasShowReel, waOrg.AllowSearchResultsPageLogo');
            $this->db->join($this->dbn.'[wlEntry]', 'wlEntry.OrgID = waOrg.OrgID');
            $this->db->join($this->dbn_live.'[wpCountry]', 'wpCountry.Country = waOrg.Country', 'left');
            $this->db->group_by('waOrg.OrgID, waOrg.OrgName, waOrg.PackageTypeCode, waOrg.HasGallery, waOrg.HasShowReel, waOrg.AllowSearchResultsPageLogo');
            $this->db->distinct();

            $this->_apply_filters($params, $skip);

            $query = $this->db->get($this->dbn.'[waOrg] waOrg');

            if(count($query->result()) > 0)
            {
                return count($query->result());
            }

            return false;
        }, $params, $skip);
    }


```
