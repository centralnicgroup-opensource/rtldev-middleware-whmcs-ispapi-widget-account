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
}
