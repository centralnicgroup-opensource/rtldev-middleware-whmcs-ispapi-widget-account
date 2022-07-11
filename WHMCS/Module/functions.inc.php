<?php

global $apiCase;
// To metigate missing functions for unit test
/**
 * @codeCoverageIgnore
 * @return bool
 */
function add_hook(string $hook_id, int $id, Closure $cb)
{
    return true;
}

/**
 * @param string $command
 * @param array<string, string> $params
 * @return array<string, mixed>
 */
function localAPI(string $command, array $params)
{
    return json_decode('{
        "result": "success",
        "totalresults": "1",
        "currencies": {
            "currency": [{
                "id": "1",
                "code": "USD",
                "prefix": "$",
                "suffix": " USD",
                "format": "1",
                "rate": "1.00000"
            }]
        }   
    }', true);
}

/**
 * @return string
 */
function formatCurrency(string $currency, int $currencyID)
{
    return $currency;
}

function keysystems_getAccountDetails()
{
    global $apiCase;
    if ($apiCase === 0) {
        // test null, failure response, not 200
        return [
            "deposit" => null,
            "amount" => null,
            "currency" => null,
            "success" => false
        ];
    } elseif ($apiCase === 1) {
        // 1 = RESPONSE_BALANCE_NO_DEPOSITS_OVERDRAFT
        return [
            "deposit" => "10000.00",
            "amount" => "-16498.09",
            "currency" => "USD",
            "success" => true
        ];
    } elseif ($apiCase === 2) {
        // 2 = RESPONSE_BALANCE_NO_DEPOSITS_NO_OVERDRAFT
        return [
            "deposit" => "0.00",
            "amount" => "16498.09",
            "currency" => "CNY",
            "success" => true
        ];
    } elseif ($apiCase === 3) {
        // 3 = RESPONSE_BALANCE_WITH_DEPOSITS_OVERDRAFT
        return [
            "deposit" => "10000.00",
            "amount" => "-16498.09",
            "currency" => "CNY",
            "success" => true
        ];
    } elseif ($apiCase === 4) {
        // 4 = RESPONSE_BALANCE_WITH_DEPOSITS_NO_OVERDRAFT
        return [
            "deposit" => "10000.00",
            "amount" => "16498.09",
            "currency" => "USD",
            "success" => "success"
        ];
    }
    return [
        "amount" => "-16498.09",
        "currency" => "USD",
        "success" => true
    ];
}
