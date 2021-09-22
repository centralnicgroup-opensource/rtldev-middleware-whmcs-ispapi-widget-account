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
use WHMCS\Config\Setting;

const ISPAPI_LOGO_URL = "https://raw.githubusercontent.com/hexonet/whmcs-ispapi-registrar/master/modules/registrars/ispapi/logo.png";
const ISPAPI_REGISTRAR_GIT_URL = "https://github.com/hexonet/whmcs-ispapi-registrar";

class IspapiBaseWidget extends \WHMCS\Module\AbstractWidget
{

    protected string $widgetid;

    public function __construct(string $id)
    {
        $this->widgetid = $id;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Fetch data that will be provided to generateOutput method
     * @return mixed data array or null in case of an error
     */
    public function getData()
    {
        $status = \App::getFromRequest("status");
        if ($status !== "") {
            $status = (int)$status;
            if (in_array($status, [0,1])) {
                Setting::setValue($this->widgetid, $status);
            }
            return [
                "success" => (int)Setting::getValue($this->widgetid) === $status
            ];
        }

        $status = Setting::getValue($this->widgetid);
        if (is_null($status)) {
            $status = 1;
        }
        return [
            "status" => (int)$status
        ];
    }

    /**
     * generate widget"s html output
     * @param mixed $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        // missing or inactive registrar Module
        if ($data["status"] === -1) {
            $gitURL = ISPAPI_REGISTRAR_GIT_URL;
            $logoURL = ISPAPI_LOGO_URL;
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

        // show our widget
        $html = "";
        if ($data["status"] === 0) {
            $html = <<<HTML
            <div class="widget-billing">
                <div class="row account-overview-widget">
                    <div class="col-sm-12">
                        <div class="item">
                            <div class="note">
                                Widget is currently disabled. Use the first icon for enabling.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            HTML;
        }
        // Data Refresh Request -> avoid including JavaScript
        if (empty($_REQUEST["refresh"])) {
            $ico = ($data["status"] === 1) ? "on" : "off";
            $wid = ucfirst($this->widgetid);
            $html = <<<HTML
            {$html}
            <script type="text/javascript">
            if (!$("#panel${wid} .widget-tools .hx-widget-toggle").length) {
                $("#panel${wid} .widget-tools").prepend(
                    `<a href="#" class="hx-widget-toggle" data-status="${data["status"]}">
                        <i class=\"fas fa-toggle-${ico}\"></i>
                    </a>`
                );
            }
            $("#panel${wid} .hx-widget-toggle").off().on("click", function (event) {
                event.preventDefault();
                const newstatus = (1 - $(this).data("status"));
                const url = WHMCS.adminUtils.getAdminRouteUrl("/widget/refresh&widget=${wid}&status=" + newstatus)
                WHMCS.http.jqClient.post(url, function (json) {
                    if (json.success && (JSON.parse(json.widgetOutput)).success) {
                        window.location.reload(); // widget refresh doesn't update the height
                    }
                }, 'json');
            });
            </script>
            HTML;
        }

        return $html;
    }
}

/**
 * ISPAPI Account Widget.
 */
class IspapiAccountWidget extends IspapiBaseWidget
{
    /** @var IspapiBalance */
    private $balanceObject = null;

    /** @var string */
    const VERSION = "3.1.6";//keep it that way (version updater, whmcs needs this accessible in public)

    private const TIME_IN_SECONDS = 120;
    private const SORT_WEIGHT = 150; // The sort weighting that determines the output position on the page

    public function __construct()
    {
        parent::__construct("ispapiAccountWidget");
        // prefer composition over inheritance
        $this->balanceObject = new IspapiBalance();
        $this->weight = self::SORT_WEIGHT; // override parent class value
        $this->cacheExpiry = self::TIME_IN_SECONDS; // override parent class value
        $this->title = "HEXONET ISPAPI Account Overview"; // override parent class value
    }

    /**
     * Fetch data that will be provided to generateOutput method
     * @return array<string, mixed> data array
     */
    public function getData()
    {
        if (class_exists(Ispapi::class)) {
            return array_merge(parent::getData(), [
                "balance" => $this->balanceObject
            ]);
        }
        // @codeCoverageIgnoreStart
        return [
            "status" => -1
        ];
        // @codeCoverageIgnoreEnd
    }

    /**
     * generate widget"s html output
     * @param mixed $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        if (isset($data["success"])) {
            return json_encode($data) ?: "[\"success\":false]";
        }

        // widget controls / status switch
        $html = parent::generateOutput($data);

        // Missing Registrar Module (-1)
        // Inactive Widget (0)
        if ($data["status"] !== 1) {
            return $html;
        }

        // generate HTML
        $balanceHTML = $data["balance"]->toHTML();
        $logo = ISPAPI_LOGO_URL;
        $html .= <<<HTML
            <div class="widget-billing">
                <div class="row account-overview-widget">
                    <div class="col-sm-6 bordered-right">
                        {$balanceHTML}
                    </div>
                    <div class="col-sm-6">
                        <div class="text-center">
                            <img src="${logo}" width="125" height="40">
                        </div>
                    </div>
                </div>
            </div>
        HTML;

        // Data Refresh Request -> avoid including JavaScript
        if (!empty($_REQUEST["refresh"])) {
            return $html;
        }

        return <<<HTML
            {$html}
            <script type="text/javascript">
            hxStartCounter("#balexpires");
            </script>
        HTML;
    }
}
class IspapiBalance
{
    /** @var array<string, mixed> */
    private $data = [];
    /** @var IspapiCurrency */
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
     * @param array<string,mixed>|string $value
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }
    /**
     * Magic getter
     * @return mixed|null
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
        if (
            empty($_REQUEST["refresh"]) // no refresh request
            && isset($_SESSION["ispapibalance"]) // data cache exists
            && (time() <= $_SESSION["ispapibalance"]["expires"]) // data cache not expired
        ) {
            $this->data = $_SESSION["ispapibalance"]["data"];
            return;
        }
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
            $_SESSION["ispapibalance"] = [
                "data" => $this->data,
                "expires" => time() + 3600,
                "ttl" => 3600
            ];
        }
    }

    /**
     * get balance data
     * @return array<string, mixed>|null
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
        $data = [
            "amount" => $amount,
            "deposit" => $deposit,
            "fundsav" => $fundsav,
            "currency" => $currency,
            "currencyID" => $currencyid,
            "hasDeposits" => $deposit > 0,
            "isOverdraft" => $fundsav < 0
        ];
        return $data;
    }

    /**
     * get formatted balance data
     * @return array<string, mixed>|null
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
     * generate balance as HTML
     * @return string
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
        $expires = $_SESSION["ispapibalance"]["expires"] - time();
        $ttl = $_SESSION["ispapibalance"]["ttl"];
        $startHTML = <<<HTML
            <div class="item text-right">
                <div class="note">Data Cache expires: <span id="balexpires" class="ttlcounter" data-ttl="{$ttl}" data-expires="{$expires}" ></span></div>
                <script type="text/javascript">
                $("#balexpires").html(hxSecondsToHms({$expires}, {$ttl}));
                </script>
            </div>
        HTML;

        $baseHTML = <<<HTML
            {$startHTML}
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
    /** @var array<string, mixed> */
    private $data = [];

    public function __construct()
    {
        // init currency
        $this->init();
    }
    /**
     * load configured currencies
     * @return void
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
        $this->data["currencies"] = $currenciesAsAssocList;
    }
    /**
     * Magic setter
     * @param array<string,mixed>|string $value
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
        return isset($this->data["currencies"][$currency]) ? $this->data["currencies"][$currency]["id"] : null;
    }
}

// @codeCoverageIgnoreStart
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
            <script>
            let hxbalcounter = null;
            function hxStartCounter(sel) {
                if (!$(sel).length || hxbalcounter !== null) {
                    return;
                }
                hxbalcounter = setInterval(function(){
                    $(sel).each(hxDecrementCounter);
                }, 1000);
            }
            function hxDecrementCounter() {
                const expires = $(this).data("expires") - 1;
                const ttl = $(this).data("ttl");
                $(this).data("expires", expires);
                $(this).html(hxSecondsToHms(expires, ttl));
            }
            function hxSecondsToHms(d, ttl) {
                d = Number(d);
                const ttls = [3600,60,1];
                let units = ["h", "m", "s"];
                let vals = [
                    Math.floor(d / 3600), // h
                    Math.floor(d % 3600 / 60), // m
                    Math.floor(d % 3600 % 60) // s
                ];
                let steps = ttls.length;
                ttls.forEach(row => {
                    if (ttl / row === 1 && ttl % row === 0){
                        steps--;
                    }
                });
                vals = vals.splice(vals.length - steps);
                units = units.splice(units.length - steps);

                let html = "";
                vals.forEach((val, idx) => {
                    html += " " + val + units[idx];
                });
                console.log(html);
                return html.substr(1);
            }
            </script>
        HTML;
    }
});
// @codeCoverageIgnoreEnd
