<?php

namespace WHMCS\Module\Registrar\Ispapi;

class Ispapi
{
    /** @var int */
    public static $apiCase = 1;

    /**
     * @param array<string, string> $command
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    public static function call(array $command, array $params = null, string $registrar = "ispapi"): array
    {
        if (self::$apiCase === 0) {
            // test null, failure response, not 200
            return [
                "PROPERTY" => [
                    "DEPOSIT" => [],
                    "AMOUNT" => [],
                    "CURRENCY" => []
                ],
                "CODE" => "500"
            ];
        } elseif (self::$apiCase === 1) {
            // 1 = RESPONSE_BALANCE_NO_DEPOSITS_OVERDRAFT
            return [
                "PROPERTY" => [
                    "DEPOSIT" => ["10000.00"],
                    "AMOUNT" => ["-16498.09"],
                    "CURRENCY" => ["USD"]
                ],
                "CODE" => "200"
            ];
        } elseif (self::$apiCase === 2) {
            // 2 = RESPONSE_BALANCE_NO_DEPOSITS_NO_OVERDRAFT
            return [
                "PROPERTY" => [
                "DEPOSIT" => ["0.00"],
                "AMOUNT" => ["16498.09"],
                "CURRENCY" => ["CNY"]
                ],
                "CODE" => "200"];
        } elseif (self::$apiCase === 3) {
            // 3 = RESPONSE_BALANCE_WITH_DEPOSITS_OVERDRAFT
            return [
                "PROPERTY" => [
                "DEPOSIT" => ["10000.00"],
                "AMOUNT" => ["-16498.09"],
                "CURRENCY" => ["CNY"]
                ],
                "CODE" => "200"];
        } elseif (self::$apiCase === 4) {
            // 4 = RESPONSE_BALANCE_WITH_DEPOSITS_NO_OVERDRAFT
            return [
                "PROPERTY" => [
                    "DEPOSIT" => ["10000.00"],
                    "AMOUNT" => ["16498.09"],
                    "CURRENCY" => ["USD"]
                ],
                "CODE" => "200"
            ];
        } else {
            return []; // @codeCoverageIgnore
        }
    }
}
