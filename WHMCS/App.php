<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class App
{
    public static function getFromRequest(string $key): string
    {
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        return "";
    }
}
