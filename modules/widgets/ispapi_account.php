<?php

/**
 * WHMCS ISPAPI Account Dashboard Widget
 *
 * This Widget allows to display your account balance.
 *
 * @see https://github.com/hexonet/whmcs-ispapi-widget-account/wiki/
 *
 * @copyright Copyright (c) Kai Schwarz, HEXONET GmbH, 2019
 * @license https://github.com/hexonet/whmcs-ispapi-widget-account/blob/master/LICENSE/ MIT License
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
namespace WHMCS\Module\Widget;

use WHMCS\Module\Registrar\Ispapi\Ispapi;

add_hook("AdminHomeWidgets", 1, function () {
    return new IspapiAccountWidget();
});

// css style
add_hook("AdminAreaHeadOutput", 1, function ($vars) {
    if ($vars["pagetitle"] === "Dashboard") {
        return <<<HTML
            <style>
                .account-overview-widget {
                    display: flex; 
                    flex-wrap: wrap; 
                    align-items: center;
                }
            </style>
        HTML;
    }
});

const ISPAPI_LOGO_URL = "https://github.com/hexonet/whmcs-ispapi-registrar/raw/master/modules/registrars/ispapi/logo.png";
const ISPAPI_REGISTRAR_GIT_URL = "https://github.com/hexonet/whmcs-ispapi-registrar";

/*
const RESPONSE_BALANCE_NO_DEPOSITS_OVERDRAFT = [
    "PROPERTY" => [
        "DEPOSIT" => ["0.00"],
        "AMOUNT" => ["-16498.09"],
        "CURRENCY" => ["CNY"]
    ],
    "CODE" => "200"
];
const RESPONSE_BALANCE_NO_DEPOSITS_NO_OVERDRAFT = [
    "PROPERTY" => [
        "DEPOSIT" => ["0.00"],
        "AMOUNT" => ["16498.09"],
        "CURRENCY" => ["CNY"]
    ],
    "CODE" => "200"
];
const RESPONSE_BALANCE_WITH_DEPOSITS_OVERDRAFT = [
    "PROPERTY" => [
        "DEPOSIT" => ["10000.00"],
        "AMOUNT" => ["-16498.09"],
        "CURRENCY" => ["CNY"]
    ],
    "CODE" => "200"
];
const RESPONSE_BALANCE_WITH_DEPOSITS_NO_OVERDRAFT = [
    "PROPERTY" => [
        "DEPOSIT" => ["10000.00"],
        "AMOUNT" => ["16498.09"],
        "CURRENCY" => ["CNY"]
    ],
    "CODE" => "200"
];
*/

/**
 * ISPAPI Account Widget.
 */
class IspapiAccountWidget extends \WHMCS\Module\AbstractWidget
{
    private $currencyObject = null;
    private $balanceObject = null;
    private $statsObject = null;
    const VERSION = "3.1.2";//keep it that way (version updater, whmcs needs this accessible in public)
    private const TIME_IN_SECONDS = 120;
    private const SORT_WEIGHT = 150; // The sort weighting that determines the output position on the page

    public function __construct()
    {
        // prefer composition over inheritance
        $this->balanceObject = new IspapiBalance();
        $this->statsObject = new IspapiStatistics();
        $this->weight = self::SORT_WEIGHT; // override parent class value
        $this->cacheExpiry = self::TIME_IN_SECONDS; // override parent class value
        $this->title = "HEXONET ISPAPI Account Overview"; // override parent class value
    }

    /**
     * Fetch data that will be provided to generateOutput method
     * @return array|null data array or null in case of an error
     */
    public function getData()
    {
        if (class_exists(Ispapi::class)) {
            return [
                "balance" => $this->balanceObject,
                "stats" => $this->statsObject
            ];
        }
        $gitURL = ISPAPI_REGISTRAR_GIT_URL;
        return <<<HTML
            <div class="widget-content-padded widget-billing">
                <div class="color-pink">
                    Please install or upgrade to the latest HEXONET ISPAPI Registrar Module.
                    <span data-toggle="tooltip" title="The HEXONET ISPAPI Registrar Module is regularly maintained, download and documentation available at github." class="glyphicon glyphicon-question-sign"></span><br/>
                    <a href="{$gitURL}">
                        <img src="{$logoURL}" width="125" height="40"/>
                    </a>
                </div>
            </div>
        HTML;
    }

    /**
     * generate widget"s html output
     * @param array $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        // generate HTML
        if (is_array($data)) {
            return $this->getFinalHTML($data["balance"], $data["stats"]);
        }
        // otherwise, error string returned
        return $data;
    }
    /**
     * generate final HTML for the generateOutput method
     * @param array $balance Account Balance
     * @param array $stats HTML String representing object stats
     * @return string HTML code
     */
    private function getFinalHTML(IspapiBalance $balance, IspapiStatistics $stats): string
    {
        $balanceHTML = $balance->toHTML();
        $statsHTML = $stats->toHTML();
        return <<<HTML
            <div class="widget-billing">
                <div class="row account-overview-widget">
                    <div class="col-sm-6 bordered-right">
                        {$balanceHTML}
                    </div>
                    <div class="col-sm-6 bordered-right">
                        {$statsHTML}
                    </div>
                </div>
            </div>
        HTML;
    }
}
class IspapiStatistics
{
    private $data = [];
    public function __construct()
    {
        // init object statistics
        $this->init();
    }
        /**
     * Magic setter
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }
    /**
     * Magic getter
     * @return null or primitive data type
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        return null;
    }
    /**
     * Magic isset function
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
    /**
     * Magic unset function
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }
    /**
     * get object statistics
     * @return void
     */
    private function init(): void
    {
        $this->data = [];
        // DEPRECATED as of RSRBE-2122
        /*$userObjectStats = Ispapi::call([
            "COMMAND" => "QueryUserObjectStatistics"
        ]);
        if ($userObjectStats["CODE"] === "200" || !empty($userObjectStats["PROPERTY"])) {
            foreach ($userObjectStats["PROPERTY"] as $key => $val) {
                if (!preg_match("/_/", $key)) {
                    $productTitle = $this->getProductTitle($key);
                    $this->data[$productTitle] = $val[0];
                }
            }
            ksort($this->data);
        }*/
    }
    /**
     * get product title for a given product id
     * @param string $productid product id
     * @return string product title if found, product id otherwise
     */
    private function getProductTitle(string $productid): string
    {
        $map = [
            "DOMAIN" => "Domains",
            "DNSZONE" => "DNS Zones",
            "MANAGEDDOMAIN" => "Domains (RegOC)",
            "SSLCERT" => "SSL Certs"
        ];
        return isset($map, $productid) ? $map[$productid] : $productid;
    }
    /**
     * generate statistics as HTML
     * @return string|null
     */
    public function toHTML(): string
    {
        $statsHTML = "";
        if (empty($this->data)) {
            $logoURL = ISPAPI_LOGO_URL;
            return <<<HTML
                <div class="text-center">
                    <img src="{$logoURL}" width="125" height="40"/>
                </div>
            HTML;
        }

        $statsHTML = "";
        foreach ($this->data as $productTitle => $count) {
            $statsHTML .= <<<HTML
                <div class="col-xs-9 col-sm-8">{$productTitle}</div>
                <div class="col-xs-3 col-sm-4 text-right">{$count}</div>
            HTML;
        }
        return <<<HTML
            <div class="row">{$statsHTML}</div>
        HTML;
    }
}
class IspapiBalance
{
    private $data = [];
    private $currencyObject = null;

    public function __construct()
    {
        // init status
        $this->init();
        // a reference to the currency instance in main class: IspapiAccountWidget
        $this->currencyObject = new IspapiCurrency();
    }
    /**
     * Magic setter
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }
    /**
     * Magic getter
     * @return null or primitive data type
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        return null;
    }
    /**
     * Magic isset function
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
    /**
     * Magic unset function
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }

    /**
     * get account status from HEXONET API
     * @return void
     */
    private function init(): void
    {
        $this->data = [];
        $accountsStatus = Ispapi::call([
            "COMMAND" => "StatusAccount"
        ]);
        //$accountsStatus = RESPONSE_BALANCE_NO_DEPOSITS_OVERDRAFT;
        //$accountsStatus = RESPONSE_BALANCE_NO_DEPOSITS_NO_OVERDRAFT;
        //$accountsStatus = RESPONSE_BALANCE_WITH_DEPOSITS_OVERDRAFT;
        //$accountsStatus = RESPONSE_BALANCE_WITH_DEPOSITS_NO_OVERDRAFT;
        if ($accountsStatus["CODE"] === "200") {
            foreach ($accountsStatus["PROPERTY"] as $property => $value) {
                $this->data[$property] = $value[0];
            }
        }
    }

    /**
     * get balance data
     * @return array|null
     */
    public function getData(): ?array
    {
        if (empty($this->data)) {
            return null;
        }
        $amount = floatval($this->data["AMOUNT"]);
        $deposit = floatval($this->data["DEPOSIT"]);
        $fundsav = $amount - $deposit;
        $currency = $this->data["CURRENCY"];
        $currencyid = $this->currencyObject->getId($currency);
        return [
            "amount" => $amount,
            "deposit" => $deposit,
            "fundsav" => $fundsav,
            "currency" => $currency,
            "currencyID" => $currencyid,
            "hasDeposits" => $deposit > 0,
            "isOverdraft" => $fundsav < 0
        ];
    }

    /**
     * get formatted balance data
     * @return array|null
     */
    public function getDataFormatted(): ?array
    {
        $data = $this->getData();
        if (is_null($data)) {
            return null;
        }
        $keys = ["amount", "deposit", "fundsav"];
        if (is_null($data["currencyID"])) {
            foreach ($keys as $key) {
                $data[$key] = number_format($data[$key], 2, ".", ",") . " " . $data["currency"];
            }
        } else {
            foreach ($keys as $key) {
                $data[$key] = formatCurrency($data[$key], $data["currencyID"]);
            }
        }
        return $data;
    }

    /**
     * generate statistics as HTML
     * @return string|null
     */
    public function toHTML(): string
    {
        $data = $this->getDataFormatted();
        if (is_null($data)) {
            return <<<HTML
                <div class="widget-content-padded widget-billing">
                    <div class="color-pink">Loading Account Data failed.</div>
                </div>
            HTML;
        }

        $balanceColor = $data["isOverdraft"] ? "pink" : "green";
        $baseHTML = <<<HTML
            <div class="item text-right">
                <div class="data color-{$balanceColor}">{$data["fundsav"]}</div>
                <div class="note">Account Balance</div>
            </div>
        HTML;
        if (!$data["hasDeposits"]) {
            return $baseHTML;
        }

        return <<<HTML
            {$baseHTML}
            <div class="item text-right">
                <div class="data color-pink">{$data["deposit"]}</div>
                <div class="note" data-toggle="tooltip" title="Deposits are automatically withdrawn from your account balance to cover impending backorders and will be returned if a backorder registration is unsuccessful.">Reserved Deposits <span class="glyphicon glyphicon-question-sign"></span></div>
            </div>
            <div class="item bordered-top text-right">
                <div class="data color-{$balanceColor}">{$data["fundsav"]}</div>
                <div class="note">Available Funds</div>
            </div>            
        HTML;
    }
}

class IspapiCurrency
{
    private $data = [];

    public function __construct()
    {
        // init currency
        $this->init();
    }
    /**
     * load configured currencies
     * @return array void
     */
    private function init(): void
    {
        $currencies = localAPI("GetCurrencies", []);
        $currenciesAsAssocList = [];
        if ($currencies["result"] === "success") {
            foreach ($currencies["currencies"]["currency"] as $idx => $d) {
                $currenciesAsAssocList[$d["code"]] = $d;
            }
        }
        $this->$currencies = $currenciesAsAssocList;
    }
    /**
     * Magic setter
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }
    /**
     * Magic getter
     * @return null or primitive data type
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        return null;
    }
    /**
     * Magic isset function
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
    /**
     * Magic unset function
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }

    /**
     * get currency id of a currency identified by given currency code
     * @param string $currency currency code
     * @return null|int currency id or null if currency is not configured
     */
    public function getId(string $currency): ?int
    {
        return isset($this->currencies[$currency]) ? $this->currencies[$currency]["id"] : null;
    }
}
