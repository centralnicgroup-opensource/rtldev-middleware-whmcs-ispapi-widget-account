<?php

namespace WHMCS\Module\Widget;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\Ispapi\Ispapi;

final class AccoundWidgetTest extends TestCase
{

    /**
     * @cover IspapiAccpuntWidget __construct
     */
    public function testAccountWidgetInstance()
    {
        $accountwidget = new IspapiAccountWidget();

        $this->assertInstanceOf('whmcs\\module\\widget\\ispapiaccountwidget', $accountwidget);

        return $accountwidget;
    }
    /**
     * @depends testAccountWidgetInstance
     */
    public function testGetData(IspapiAccountWidget $accountwidget)
    {
        $data = $accountwidget->getData();
        $this->assertArrayHasKey('balance', $data);
        $this->assertArrayHasKey('stats', $data);
        return $data;
    }
    /**
     * @depends testGetData
     * @depends testAccountWidgetInstance
     */
    public function testGenerateOutput(array $data, IspapiAccountWidget $accountwidget)
    {
        // array sent - check for html output
        // this depends on other two functions

        // bool sent - check for html output
        $data = false;
        $result = $accountwidget->generateOutput($data);
        $matcher = "Please install or upgrade to the latest HEXONET ISPAPI Registrar Module.";
        $this->assertStringContainsString($matcher, $result);
    }
    // public function testGetFinalHTML(){

    // }
}
