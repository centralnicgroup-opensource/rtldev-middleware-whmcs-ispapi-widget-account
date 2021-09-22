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
     * @depends testAccountWidgetInstance
     */
    public function testGenerateOutput(IspapiAccountWidget $accountwidget)
    {
        // enabled widget, api data available
        $balanceObject = new IspapiBalance();
        $statsObject = new IspapiStatistics();
        $result = $accountwidget->generateOutput([
            "balance" => $balanceObject,
            "stats" => $statsObject,
            "status" => 1
        ]);
        $matcher = "<div class=\"data color-pink\">-26498.09</div>";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "window.location.reload()";
        $this->assertStringContainsString($matcher, $result);

        // registrar module missing or inactive
        $result = $accountwidget->generateOutput([ "status" => -1 ]);
        $matcher = "Please install or upgrade to the latest HEXONET ISPAPI Registrar Module.";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "window.location.reload()";
        $this->assertStringNotContainsString($matcher, $result);

        // widget status has changed via xhr req
        $result = $accountwidget->generateOutput([ "success" => true ]);
        $matcher = "{\"success\":true}";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "window.location.reload()";
        $this->assertStringNotContainsString($matcher, $result);

        // refresh request - main js logics shall not be returned
        $_REQUEST["refresh"] = 1;
        $result = $accountwidget->generateOutput([
            "balance" => $balanceObject,
            "stats" => $statsObject,
            "status" => 1
        ]);
        $matcher = "<div class=\"data color-pink\">-26498.09</div>";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "window.location.reload()";
        $this->assertStringNotContainsString($matcher, $result);
    }
}
