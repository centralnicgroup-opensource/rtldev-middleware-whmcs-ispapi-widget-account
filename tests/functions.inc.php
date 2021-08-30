<?php

// To metigate missing functions for unit test

function add_hook($hook_id, $id, $cb)
{
    return true;
}

function localAPI($command, $params)
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

function formatCurrency($currency, $currencyID)
{
    return $currency;
}
