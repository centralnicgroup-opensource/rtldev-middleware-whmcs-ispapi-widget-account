<?php

namespace WHMCS\Config;

class Setting
{
    /**
     * @var array<string, mixed> $config
     */
    private static array $config = [
        "status" => 0
    ];

    public static function getValue(string $setting): ?string
    {
        if (isset(self::$config["status"])) {
            return self::$config["status"];
        }
        return null;
    }
    public static function setValue(string $key, mixed $value): void
    {
        $value = trim($value);
        self::$config[$key] = (string)$value;
    }
}
