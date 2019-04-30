<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
namespace ISPAPIWIDGET;

use WHMCS\Database\Capsule;
use PDO;

if (defined("ROOTDIR")) {
    require_once(implode(DIRECTORY_SEPARATOR, array(ROOTDIR,"includes","registrarfunctions.php")));
}

/**
 * PHP Helper Class
 *
 * @copyright  2018 HEXONET GmbH, MIT License
 */
class Helper
{
    public static $currencies = null;
    public static $paymentmethods = null;

    /*
     * Helper to send API command to the given registrar. Returns the response
     *
     * @param string $registrar The registrar
     * @param string $command The API command to send
     *
     * @return array The response from the API
     */
    public static function APICall($registrar, $command)
    {
        $registrarconfigoptions = getregistrarconfigoptions($registrar);
        $registrar_config = call_user_func($registrar."_config", $registrarconfigoptions);
        return call_user_func($registrar."_call", $command, $registrar_config);
    }

    /*
     * Helper to send API Response to the given registrar. Returns the parsed response
     *
     * @param string $registrar The registrar
     * @param string $response The API response to send
     *
     * @return array The parsed response from the API
     */
    public static function parseResponse($registrar, $response)
    {
        return call_user_func($registrar."_parse_response", $response);
    }

    /*
     * Helper to send SQL call to the Database with Capsule
     * Set $debug = true in the function to have DEBUG output in the JSON string
     *
     * @param string $sql The SQL query
     * @param array $params The parameters of the query DEFAULT = NULL
     * @param $fetchmode The fetching mode of the query (fetch, fetchall, execute) - DEFAULT = fetch

     * @return array response where boolean property "success" tells you if the query was successful or not
     * and property "result" only exists in case of success and covers the expected response format.
     * In case of execute failed (or thrown error), check property "errormsg" for the error details.
     */
    public static function SQLCall($sql, $params = null, $fetchmode = "fetch")
    {
        $debug = false;
        $result = array(
            "success" => false
        );

        // replace NULL values with empty string
        // check if this is still necessary after we switched to PHP-SDK
        $params = array_map(function ($v) {
            return (is_null($v)) ? "" : $v;
        }, $params);

        // for INSERTs apply a way to dynamically generate list of fields
        // and their values
        if (preg_match("/^INSERT /i", $sql)) {
            $keys = array_keys($params);
            $fkeys = implode(", ", preg_replace("/:/", " ", $keys));
            $fvals = implode(", ", $keys);
            $sql = preg_replace("/\{\{KEYS\}\}/", $fkeys, $sql, 1);
            $sql = preg_replace("/\{\{VALUES\}\}/", $fvals, $sql, 1);
        }

        // now execute SQL statement and return result in requested format
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare($sql);
            $result["success"] = $stmt->execute($params);
            switch ($fetchmode) {
                case "execute":
                    // we won't have a result property as not expected
                    break;
                case "fetchall":
                    $result["result"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                default:
                    $result["result"] = $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
            }
            if (!$result["success"]) { // execute failed
                // return the reason: http://php.net/manual/de/pdostatement.errorinfo.php
                $result["errormsg"] = implode(", ", $stmt->errorInfo());
            }
            $pdo->commit();
        } catch (Exception $e) {
            logModuleCall(
                'provisioningmodule',
                __FUNCTION__,
                $params,
                $e->getMessage(),
                $e->getTraceAsString()
            );
            $pdo->rollBack();
            $result["errormsg"] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Return list of available Payment Gateways
     *
     * @return array list of payment gateways
     */
    public static function getPaymentMethods()
    {
        if (!self::$paymentmethods) {
            self::$paymentmethods = array();
            $r = localAPI("GetPaymentMethods", array());
            if ($r["result"]) {
                foreach ($r["paymentmethods"]["paymentmethod"] as $pm) {
                    self::$paymentmethods[$pm["module"]] = $pm["displayname"];
                }
            }
        }
        return self::$paymentmethods;
    }


    /**
     * load configured currencies
     * @return array assoc array, list of currencies where currency code is array key
     */
    public static function getCurrencies()
    {
        if (!self::$currencies) {
            self::$currencies = array();
            $results = localAPI('GetCurrencies', array());
            if ($results["result"]=="success") {
                foreach ($results["currencies"]["currency"] as $idx => $d) {
                    self::$currencies[$d["code"]] = $d;
                }
            }
        }
        return self::$currencies;
    }

    /**
     * get currency id of a currency identified by given currency code
     * @param string $currency currency code
     * @return null|int currency id or null if currency is not configured
     */
    private function getCurrencyId($currency)
    {
        $cs = self::getCurrencies();
        return (!isset($cs[$currency]) ? null : $cs[$currency]["id"]);
    }

    /**
     * Get client details by given email address
     *
     * @return array|boolean the client id or false if not found
     */
    public static function getClientsDetailsByEmail($email)
    {
        $r = localAPI('GetClientsDetails', array('email' => $email));
        if ($r["result"]=="success") {
            $details = array();
            return $r["client"];
        }
        return false;
    }

    /**
     * Create a new client by given API contact data and return the client id.
     *
     * @param array $contact StatusContact PROPERTY data from API
     * @param string $currency currency id
     *
     * @return string|bool client id or false in error case
     */
    public static function addClient($contact, $currencyid, $password)
    {
        $phone = preg_replace('/[^0-9 ]/', '', $contact["PHONE"][0]);//only numbers and spaces allowed
        $zip = preg_replace('/[^0-9a-zA-Z ]/', '', $contact["ZIP"][0]);
        $request = array(
            "firstname" => $contact["FIRSTNAME"][0],
            "lastname" => $contact["LASTNAME"][0],
            "email" => $contact["EMAIL"][0],
            "address1" => $contact["STREET"][0],
            "city" => $contact["CITY"][0],
            "state" => $contact["STATE"][0],
            "postcode" => $zip ? $zip : "N/A",
            "country" => strtoupper($contact["COUNTRY"][0]),
            "phonenumber" => $phone,
            "password2" => $password,
            "currency" => $currencyid,
            "language" => "english"
        );
        if (!empty($contact["ORGANIZATION"][0])) {
            $request["companyname"] = $contact["ORGANIZATION"][0];
        }
        if (!empty($contact["STREET"][1])) {
            $request["address2"] = $contact["STREET"][1];
        }
        $result = localAPI('AddClient', $request);
        if ($r["result"] == "success") {
            return Helper::getClientsDetails($contact["EMAIL"][0]);
        }
        return false;
    }

    /**
     * Check if a domain already exists in WHMCS database
     * @param string $domain domain name
     * @return boolean check result
     */
    public static function checkDomainExists($domain)
    {
        $r = localAPI('GetClientsDomains', array(
            'domain' => $domain,
            'limitnum' => 1
        ));
        if ($r["result"] == "success") {
            return $r["totalresults"] > 0;
        }
        return false;
    }

    /**
     * Create a domain by given data
     *
     * @param string $domain domain name
     * @param array $apidata StatusDomain PROPERTY data from API
     * @param string $gateway payment gateway
     * @param string $clientid client id
     * @param string $recurringamount recurring amount
     *
     * @return bool domain creation result
     */
    public static function createDomain($domain, $apidata, $gateway, $clientid, $recurringamount)
    {
        $info = array(
            ":userid" => $clientid,
            ":orderid" => 0,
            ":type" => "Register",
            ":registrationdate" => $apidata["CREATEDDATE"][0],
            ":domain" => strtolower($domain),
            ":firstpaymentamount" => $recurringamount,
            ":recurringamount" => $recurringamount,
            ":paymentmethod" => $gateway,
            ":registrar" => "ispapi",
            ":registrationperiod" => 1,
            ":expirydate" => $apidata["PAIDUNTILDATE"][0],
            ":subscriptionid" => "",
            ":status" => "Active",
            ":nextduedate" => $apidata["PAIDUNTILDATE"][0],
            ":nextinvoicedate" => $apidata["PAIDUNTILDATE"][0],
            ":dnsmanagement" => "on",
            ":emailforwarding" => "on"
        );
        $r = Helper::SQLCall("INSERT INTO tbldomains ({{KEYS}}) VALUES ({{VALUES}})", $info, "execute");
        return $r["success"];
    }

    /**
     * import an existing domain from HEXONET API.
     *
     * @param string $domain domain name
     * @param string $registrar registrar id
     * @param string $gateway payment gateway
     * @param string $currency currency
     * @param string $password the default password we set for newly created customers
     * @param array  $contacts contact data container
     *
     * @return array where property "success" (boolean) identifies the import result and property "msgid" the translation/language key
     */
    public static function importDomain($domain, $registrar, $gateway, $currency, $password, &$contacts)
    {
        if (!preg_match('/\.(.*)$/i', $domain, $m)) {
            return array(
                success => false,
                msgid => 'domainnameinvaliderror'
            );
        }
        $tld = strtolower($m[1]);
        if (Helper::checkDomainExists($domain)) {
            return array(
                success => false,
                msgid => 'alreadyexistingerror'
            );
        }
        $r = Helper::APICall($registrar, array(
            "COMMAND" => "StatusDomain",
            "DOMAIN"  => $domain
        ));
        if (!($r["CODE"] == 200)) {
            return array(
                success => false,
                msgid => null,
                msg => $r["DESCRIPTION"]
            );
        }
        $registrant = $r["PROPERTY"]["OWNERCONTACT"][0];
        if (!$registrant) {
            return array(
                success => false,
                msgid => "registrantmissingerror"
            );
        }
        if (!isset($contacts[$registrant])) {
            $r2 = Helper::APICall($registrar, array(
                "COMMAND" => "StatusContact",
                "CONTACT"  => $registrant
            ));
            if (!($r2["CODE"] == 200)) {
                return array(
                    success => false,
                    msgid => "registrantfetcherror"
                );
            }
            $contacts[$registrant] = $r2["PROPERTY"];
        }
        $contact = $contacts[$registrant];
        if ((!$contact["EMAIL"][0]) || (preg_match('/null$/i', $contact["EMAIL"][0]))) {
            $contact["EMAIL"][0] = "info@".$domain;
        }
        if (empty($contact["PHONE"][0])) {
            return array(
                success => false,
                msgid => "registrantcreateerrornophone"
            );
        }
        $client = Helper::getClientsDetailsByEmail($contact["EMAIL"][0]);
        if (!$client) {
            $client = Helper::addClient($contact, $currency, $password);
            if (!$client) {
                return array(
                    success => false,
                    msgid => "registrantcreateerror"
                );
            }
        }
        $domainprices = localAPI('GetTLDPricing', array(
            'currencyid' => $client["currency"]
        ));
        if (!$domainprices["result"] == "success") {
            return array(
                success => false,
                msgid => "tldrenewalpriceerror"
            );
        }
        if (!isset($domainprices["pricing"][$tld]['renew']['1'])) {
            return array(
                success => false,
                msgid => "tldrenewalpriceerror"
            );
        }
        $result = Helper::createDomain($domain, $r["PROPERTY"], $gateway, $client["id"], $domainprices["pricing"][$tld]['renew']['1']);
        if (!$result) {
            return array(
                success => false,
                msgid => "domaincreateerror"
            );
        }
        return array(
            success => true,
            msgid => "ok"
        );
    }

    public static $stringCharset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public static function generateRandomString($length = 10)
    {
        $characters = Helper::$stringCharset;
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

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

$module_version = "1.0.2";

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
        $r = Helper::APICall('ispapi', array('COMMAND' => 'StatusAccount'));
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
        $r = Helper::APICall('ispapi', array('COMMAND' => 'QueryUserObjectStatistics'));
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
