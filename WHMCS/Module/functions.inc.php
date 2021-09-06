<?php

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
 * @return string
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
