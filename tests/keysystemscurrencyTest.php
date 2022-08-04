<?php

namespace WHMCS\Module\Widget;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\Keysystems\Keysystems;

final class KeysystemsCurrencyTest extends TestCase
{
    /**
     * @cover KeysystemsCurrency __construct
     */
    public function testCurrencyInstance()
    {
        $currency = new KeysystemsCurrency();
        // whmcs\\module\\widget\\Keysystemscurrency
        $this->assertInstanceOf('WHMCS\Module\Widget\KeysystemsCurrency', $currency);
        return $currency;
    }
    /**
     * @depends testCurrencyInstance
     */
    public function testGetId(KeysystemsCurrency $currency)
    {
        $id = $currency->getId("USD");
        $this->assertEquals(1, $id);
    }
}
