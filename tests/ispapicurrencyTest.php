<?php

namespace WHMCS\Module\Widget;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\Ispapi\Ispapi;

final class IspapiCurrencyTest extends TestCase
{
    /**
     * @cover IspapiCurrency __construct
    */
    public function testCurrencyInstance()
    {
        $currency = new IspapiCurrency();
        // whmcs\\module\\widget\\ispapicurrency
        $this->assertInstanceOf('WHMCS\Module\Widget\IspapiCurrency', $currency);
        return $currency;
    }
    /**
     * @depends testCurrencyInstance
     */
    public function testGetId(IspapiCurrency $currency)
    {
        $id = $currency->getId("USD");
        $this->assertEquals(1, $id);
    }
    /**
     * @cover magic getter
     * @depends testCurrencyInstance
     */
    public function testMagicMethods(IspapiCurrency $currency)
    {
        // __get - null case
        $this->assertNull($currency->__get("key"));
        // __set
        $currency->__set("key", "value");
        // __get - test previouse set value
        $this->assertEquals($currency->__get("key"), "value");
        // __isset
        $this->assertTrue($currency->__isset("key"), true);
        // __unset
        $currency->__unset("key");
        // __get - test unset
        $this->assertNull($currency->__get("key"));
    }
}
