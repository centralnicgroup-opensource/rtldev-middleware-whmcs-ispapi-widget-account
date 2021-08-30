<?php

namespace WHMCS\Module;

abstract class AbstractWidget
{
    protected $title = null;
    protected $description = null;
    protected $columns = 1;
    protected $weight = 100;
    protected $wrapper = true;
    protected $cache = false;
    protected $cachePerUser = false;
    protected $cacheExpiry = 3600;
    protected $requiredPermission = "";
    protected $draggable = true;
    protected $adminUser = null;

    abstract public function getData();
    abstract public function generateOutput($data);
}
