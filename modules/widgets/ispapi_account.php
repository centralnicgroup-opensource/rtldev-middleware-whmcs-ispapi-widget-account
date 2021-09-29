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

/**
 * ISPAPI Account Widget.
 */
class IspapiAccountWidget extends \WHMCS\Module\AbstractWidget
{
    /** @var string */
    const VERSION = "3.1.7";//keep it that way (version updater, whmcs needs this accessible in public)

    /** @var string */
    protected $title = 'HEXONET Account Overview';
    /** @var int */
    protected $cacheExpiry = 120;
    /** @var int */
    protected $weight = 150;

    /** @var string */
    public static $widgetid = "IspapiAccountWidget";
    /** @var int */
    public static $sessionttl = 3600; // 1h

    /**
     * Fetch data that will be provided to generateOutput method
     * @return array<string, mixed> data array
     */
    public function getData()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = self::$widgetid;

        // dependendy missing
        // @codeCoverageIgnoreStart
        if (!class_exists(Ispapi::class)) {
            return [
                "status" => -1,
                "widgetid" => $id
            ];
        }
        // @codeCoverageIgnoreEnd

        // status toggle
        $status = \App::getFromRequest("status");
        if ($status !== "") {
            $status = (int)$status;
            if (in_array($status, [0,1])) {
                Setting::setValue($id . "status", $status);
            }
        }

        // hidden widgets -> don't load data
        $isHidden = in_array($id, $this->adminUser->hiddenWidgets);
        if ($isHidden) {
            return [
                "status" => 0,
                "widgetid" => $id
            ];
        }

        // load data
        $status = Setting::getValue($id . "status");
        $data = [
            "status" => is_null($status) ? 1 : (int)$status,
            "widgetid" => $id
        ];
        if (
            !empty($_REQUEST["refresh"]) // refresh request
            || !isset($_SESSION[$id]) // Session not yet initialized
            || (time() > $_SESSION[$id]["expires"]) // data cache expired
        ) {
            $_SESSION[$id] = [
                "expires" => time() + self::$sessionttl,
                "ttl" =>  + self::$sessionttl
            ];
        }

        return array_merge($data, [
            "balance" => new IspapiBalance()
        ]);
    }

    /**
     * generate widget"s html output
     * @param mixed $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        // widget controls / status switch
        // missing or inactive registrar Module
        if ($data["status"] === -1) {
            $html = <<<HTML
                <div class="widget-content-padded widget-billing">
                    <div class="color-pink">
                        Please install or upgrade to the latest HEXONET ISPAPI Registrar Module.
                        <span data-toggle="tooltip" title="The HEXONET ISPAPI Registrar Module is regularly maintained, download and documentation available at github." class="glyphicon glyphicon-question-sign"></span><br/>
                        <a href="https://github.com/hexonet/whmcs-ispapi-registrar">
                            <img src="https://raw.githubusercontent.com/hexonet/whmcs-ispapi-registrar/master/modules/registrars/ispapi/logo.png" width="125" height="40"/>
                        </a>
                    </div>
                </div>
            HTML;
        } else {
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
            $expires = $_SESSION[$data["widgetid"]]["expires"] - time();
            $ttl = $_SESSION[$data["widgetid"]]["ttl"];
            $ico = ($data["status"] === 1) ? "on" : "off";
            $wid = $data["widgetid"];
            $status = $data["status"];

            $html .= <<<HTML
                <script type="text/javascript">
                // fn shared with other widgets
                function hxRefreshWidget(widgetName, requestString, cb) {
                    const panelBody = $('.panel[data-widget="' + widgetName + '"] .panel-body');
                    const url = WHMCS.adminUtils.getAdminRouteUrl('/widget/refresh&widget=' + widgetName + '&' + requestString);
                    panelBody.addClass('panel-loading');
                    return WHMCS.http.jqClient.post(url, function(data) {
                        panelBody.html(data.widgetOutput);
                        panelBody.removeClass('panel-loading');
                    }, 'json').always(cb);
                }
                if (!$("#panel${wid} .widget-tools .hx-widget-toggle").length) {
                    $("#panel${wid} .widget-tools").prepend(
                        `<a href="#" class="hx-widget-toggle" data-status="${status}">
                            <i class=\"fas fa-toggle-${ico}\"></i>
                        </a>`
                    );
                } else {
                    $("a.hx-widget-toggle").data("status", {$status});
                }
                if (!$("#hxbalexpires").length) {
                    $("#panel${wid} .widget-tools").prepend(
                        `<a href="#" class="hx-widget-expires" data-expires="${expires}" data-ttl="${ttl}">
                            <span id="hxbalexpires" class="ttlcounter"></span>
                        </a>`
                    );
                }
                $("#hxbalexpires")
                    .data("ttl", {$ttl})
                    .data("expires", {$expires})
                    .html(hxSecondsToHms({$expires}, {$ttl}));
                if ($("#panel${wid} .hx-widget-toggle").data("status") === 1) {
                    $("a.hx-widget-expires").show();
                } else {
                    $("a.hx-widget-expires").hide();
                }
                $("#panel${wid} .hx-widget-toggle").off().on("click", function (event) {
                    event.preventDefault();
                    const icon = $(this).find("i[class^=\"fas fa-toggle-\"]");
                    const mythis = this;
                    const widget = $(this).closest('.panel').data('widget');
                    const newstatus = (1 - $(this).data("status"));
                    icon.attr("class", "fas fa-spinner fa-spin");
                    hxRefreshWidget(widget, "refresh=1&status=" + newstatus, function(){
                        icon.attr("class", "fas fa-toggle-" + ((newstatus === 0) ? "off" : "on"));
                        $(mythis).data("status", newstatus);
                        packery.fit(mythis);
                        packery.shiftLayout();
                    })
                });
                </script>
            HTML;
        }

        // Missing Registrar Module (-1)
        // Inactive Widget (0)
        if ($data["status"] !== 1) {
            return $html;
        }

        // generate HTML
        $balanceHTML = ($data["balance"])->toHTML();
        $html .= <<<HTML
            <div class="widget-billing">
                <div class="row account-overview-widget">
                    <div class="col-sm-6 bordered-right">
                        {$balanceHTML}
                    </div>
                    <div class="col-sm-6">
                        <div class="text-center">
                            <img src="https://raw.githubusercontent.com/hexonet/whmcs-ispapi-registrar/master/modules/registrars/ispapi/logo.png" width="125" height="40">
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
            hxStartCounter("#hxbalexpires");
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
        if (isset($_SESSION[IspapiAccountWidget::$widgetid]["data"])) { // data cache exists
            $this->data = $_SESSION[IspapiAccountWidget::$widgetid]["data"];
        } else {
            $this->data = [];
            $accountsStatus = Ispapi::call([
                "COMMAND" => "StatusAccount"
            ]);
            if ($accountsStatus["CODE"] === "200") {
                foreach ($accountsStatus["PROPERTY"] as $property => $value) {
                    $this->data[$property] = $value[0];
                }
                $_SESSION[IspapiAccountWidget::$widgetid]["data"] = $this->data;
            }
        }

        // a reference to the currency instance in main class: IspapiAccountWidget
        $this->currencyObject = new IspapiCurrency();
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
        $expires = $_SESSION[IspapiAccountWidget::$widgetid]["expires"] - time();
        $ttl = $_SESSION[IspapiAccountWidget::$widgetid]["ttl"];

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
    /** @var array<string, mixed> */
    private $data = [];

    public function __construct()
    {
        // init currency
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
     * get currency id of a currency identified by given currency code
     * @param string $currency currency code
     * @return null|int currency id or null if currency is not configured
     */
    public function getId(string $currency): ?int
    {
        return (isset($this->data["currencies"][$currency])) ?
            $this->data["currencies"][$currency]["id"] :
            null;
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
            function hxStartCounter(sel) {
                if (!$(sel).length) {
                    return;
                }
                setInterval(function(){
                    $(sel).each(hxDecrementCounter);
                }, 1000);
            }
            function hxDecrementCounter() {
                let expires = $(this).data("expires") - 1;
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
                return html.substr(1);
            }
            </script>
        HTML;
    }
});
// @codeCoverageIgnoreEnd
