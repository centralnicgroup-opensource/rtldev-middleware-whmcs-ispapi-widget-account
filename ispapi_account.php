<?php
namespace ISPAPIWIDGET;

use ISPAPINEW\Helper;

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

$module_version = "1.0.1";

add_hook('AdminHomeWidgets', 1, function () {
    return new IspapiAccountWidget();
});

/**
 * ISPAPI Account Widget.
 */
class IspapiAccountWidget extends \WHMCS\Module\AbstractWidget
{
    protected $title = 'HEXONET ISPAPI Account Overview';
    protected $description = '';
    protected $weight = 150;
    protected $columns = 1;
    protected $cache = false;
    protected $cacheExpiry = 120;
    protected $requiredPermission = '';
    protected $currencies = null;

    /**
     * load configured currencies
     * @return array assoc array, list of currencies where currency code is array key
     */
    private function getCurencies()
    {
        $results = localAPI('GetCurrencies', array());
        $currencies = array();
        if ($results["result"]=="success") {
            foreach ($results["currencies"]["currency"] as $idx => $d) {
                $currencies[$d["code"]] = $d;
            }
        }
        return $currencies;
    }

    /**
     * get currency id of a currency identified by given currency code
     * @param string $currency currency code
     * @return null|int currency id or null if currency is not configured
     */
    private function getCurrencyId($currency)
    {
        if (is_null($this->currencies)) {
            $this->currencies = $this->getCurencies();
        }
        if (!isset($this->currencies[$currency])) {
            return null;
        }
        return $this->currencies[$currency]["id"];
    }

    /**
     * get account status from HEXONET API
     * @return array|null account balance data or null in case of an error
     */
    private function getAccountStatus()
    {
        $r = \ISPAPI\Helper::APICall('ispapi', array('COMMAND' => 'StatusAccount'));
        if ($r["CODE"]!="200") {
            return null;
        }
        $balance = array();
        foreach ($r["PROPERTY"] as $key => $val) {
            $balance[$key] = $val[0];
        }
        return $balance;
    }

    /**
     * get object statistics
     * @return array|null object statistics or null in case of an error
     */
    private function getObjectStatistics()
    {
        $stats = array();
        $r = \ISPAPI\Helper::APICall('ispapi', array('COMMAND' => 'QueryUserObjectStatistics'));
        if ($r["CODE"]!="200" || empty($r["PROPERTY"])) {
            return null;
        }
        foreach ($r["PROPERTY"] as $key => $val) {
            $stats[$key] = $val[0];
        }
        return $stats;
    }

    /**
     * return html code for error case specified by given error message
     * @param string $errMsg error message to show
     * @return string html code
     */
    private function returnError($errMsg)
    {
        return <<<EOF
                <div class="widget-content-padded widget-billing">
                    <div class="color-pink">$errMsg</div>
                </div>
EOF;
    }

    /**
     * get product title for a given product id
     * @param string $productid product id
     * @return string product title if found, product id otherwise
     */
    private function getProductTitle($productid)
    {
        $map = array(
            "DOMAIN" => "Domains",
            "DNSZONE" => "DNS Zones",
            "MANAGEDDOMAIN" => "Domains (RegOC)",
            "SSLCERT" => "SSL Certs"
        );
        return isset($map, $productid) ? $map[$productid] : $productid;
    }

    /**
     * Fetch data that will be provided to generateOutput method
     * @return array|null data array or null in case of an error
     */
    public function getData()
    {
        if (!class_exists('ISPAPI\\Helper')) {
            return null;
        }
        return array(
            "balance" => $this->getAccountStatus(),
            "stats" => $this->getObjectStatistics(),
            "currencies" => $this->getCurencies()
        );
    }

    /**
     * generate widget's html output
     * @param array $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        if (is_null($data)) {
            $logo_url = "https://raw.githubusercontent.com/hexonet/whmcs-ispapi-registrar/master/registrars/ispapi/logo.gif";
            $git_url = "https://github.com/hexonet/whmcs-ispapi-registrar";
            return $this->returnError(
                'Please install and activate the HEXONET ISPAPI Registrar Module. ' .
                '<span data-toggle="tooltip" title="The HEXONET ISPAPI Registrar Module is regularly maintained, ' .
                'download and documentation available at github." class="glyphicon glyphicon-question-sign"></span><br/>' .
                '<a href="' . $git_url . '">' .
                '<img src="' . $logo_url . '" width="125" height="40"/>' .
                '</a>'
            );
        }
        $balance = $data["balance"];
        if (is_null($balance)) {
            return $this->returnError('Connection issue. Check registrar module configuration.');
        }
        $amount = floatval($balance["AMOUNT"]);
        $currency = $balance["CURRENCY"];
        $currencyid = $this->getCurrencyId($currency);
        $deposit = floatval($balance["DEPOSIT"]);
        $fundsav = $amount - $deposit;
        if (is_null($currencyid)) {
            return $this->returnError('Please configure currency <a href="configcurrencies.php">"' . $currency . '"</a>.');
        }
        $stats = false;
        if (!is_null($data["stats"])) {
            ksort($data["stats"]);
            $stats = '<div class="row">';
            foreach ($data["stats"] as $key => $val) {
                if (!preg_match("/_/", $key)) {
                    $stats .= (
                        '<div class="col-xs-9 col-sm-8">' . $this->getProductTitle($key) . '</div>' .
                        '<div class="col-xs-3 col-sm-4 text-right">' . $val . '</div>'
                    );
                }
            }
            $stats .= '</div>';
        }
        return (
            '<div class="widget-billing">' .
              '<div class="row" style="display: flex; flex-wrap: wrap; align-items: center;">' .
                '<div class="col-sm-6 bordered-right">' .
                    '<div class="item text-right">' .
                        '<div class="data color-' . ($amount >= 0 ? "green" : "pink") . '">' .
                            formatCurrency($amount, $currencyid) .
                        '</div>' .
                        '<div class="note">Account Balance</div>' .
                    '</div>' .
                    (($deposit > 0) ?
                    '<div class="item text-right">' .
                        '<div class="data color-pink">- ' .
                            formatCurrency($deposit, $currencyid) .
                        '</div>' .
                        '<div class="note" data-toggle="tooltip" title="Deposits are automatically withdrawn from your account balance to cover impending backorders and will be returned if a backorder registration is unsuccessful.">Reserved Deposits <span class="glyphicon glyphicon-question-sign"></span></div>' .
                    '</div>' .
                    '<div class="item bordered-top text-right">' .
                        '<div class="data color-' . ($amount >= 0 ? "green" : "pink") . '">' .
                            formatCurrency($fundsav, $currencyid) .
                        '</div>' .
                        '<div class="note">Available Funds</div>' .
                    '</div>' : ''
                    ) .
                '</div>' .
                '<div class="col-sm-6 bordered-right">' .
                    ($stats ? $stats : '<div class="text-center"><img src="../modules/registrars/ispapi/logo.gif" width="125" height="40"/></div>') .
                '</div>' .
              '</div>' .
            '</div>'
        );
    }
}
