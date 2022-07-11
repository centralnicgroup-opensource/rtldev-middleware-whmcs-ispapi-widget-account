<?php

namespace WHMCS\Config;

class Setting
{
    /**
     * @var array<string, mixed> $config
     */
    private static $config = [
        "status" => 0
    ];

    public static function getValue(string $setting): ?string
    {
        if (isset(self::$config[$setting])) {
            return self::$config[$setting];
        }
        return null;
    }
    /**
     * set the value for a key
     * @param string $key key name
     * @param mixed $value value
     */
    public static function setValue(string $key, $value): void
    {
        $value = trim($value);
        self::$config[$key] = (string)$value;
    }
}
