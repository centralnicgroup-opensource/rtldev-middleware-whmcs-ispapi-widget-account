<?php

namespace WHMCS\Module;

class Registrar
{
    public static $apiCase = 0;
    public function __construct()
    {
    }

    public function load()
    {
        return true;
    }

    public function isActivated()
    {
        return true;
    }
}
