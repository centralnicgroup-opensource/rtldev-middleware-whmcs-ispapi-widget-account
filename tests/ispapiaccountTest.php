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
        return $data;
    }
    /**
     * @depends testAccountWidgetInstance
     */
    public function testGenerateOutput(IspapiAccountWidget $accountwidget)
    {
        // enabled widget, api data available
        $_SESSION[IspapiAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        $balanceObject = new IspapiBalance();
        $result = $accountwidget->generateOutput([
            "balance" => $balanceObject,
            "status" => 1,
            "widgetid" => "IspapiAccountWidget"
        ]);
        $matcher = "<div class=\"data color-pink\">-26498.09</div>";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "hxStartCounter(\"#hxbalexpires\");";
        $this->assertStringContainsString($matcher, $result);

        // registrar module missing or inactive
        $result = $accountwidget->generateOutput([
            "status" => -1,
            "widgetid" => "IspapiAccountWidget"
        ]);
        $matcher = "Please install or upgrade to the latest HEXONET ISPAPI Registrar Module.";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "hxStartCounter(\"#hxbalexpires\");";
        $this->assertStringNotContainsString($matcher, $result);

        // refresh request - main js logics shall not be returned
        $_REQUEST["refresh"] = 1;
        // enabled widget, api data available
        $result = $accountwidget->generateOutput([
            "balance" => $balanceObject,
            "status" => 1,
            "widgetid" => "IspapiAccountWidget"
        ]);
        $matcher = "<div class=\"data color-pink\">-26498.09</div>";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "hxStartCounter(\"#hxbalexpires\");";
        $this->assertStringNotContainsString($matcher, $result);
    }
}
