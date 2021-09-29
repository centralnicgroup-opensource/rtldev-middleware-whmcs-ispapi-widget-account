<?php

namespace WHMCS\Module;

abstract class AbstractWidget
{
    /** @var string */
    protected $title = "";
    /** @var string */
    protected $description = "";
    /** @var int */
    protected $columns = 1;
    /** @var int */
    protected $weight = 100;
    /** @var bool */
    protected $wrapper = true;
    /** @var bool */
    protected $cache = false;
    /** @var bool */
    protected $cachePerUser = false;
    /** @var int */
    protected $cacheExpiry = 3600;
    /** @var string */
    protected $requiredPermission = "";
    /** @var bool */
    protected $draggable = true;
    /** @var \stdClass */
    protected $adminUser;

    abstract public function getData(); /** @phpstan-ignore-line */
    abstract public function generateOutput(string $data); /** @phpstan-ignore-line */

    public function __construct()
    {
        $this->adminUser = new \stdClass();
        $this->adminUser->hiddenWidgets = [];
    }
}
