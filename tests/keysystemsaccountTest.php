<?php

namespace WHMCS\Module\Widget;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\Keysystems\Keysystems;

final class AccoundWidgetTest extends TestCase
{
    /**
     * @cover KeysystemsAccpuntWidget __construct
     */
    public function testAccountWidgetInstance()
    {
        $accountwidget = new KeysystemsAccountWidget();

        $this->assertInstanceOf('whmcs\\module\\widget\\Keysystemsaccountwidget', $accountwidget);

        return $accountwidget;
    }
    /**
     * @depends testAccountWidgetInstance
     */
    public function testGetData(KeysystemsAccountWidget $accountwidget)
    {
        $data = $accountwidget->getData();
        $this->assertArrayHasKey('balance', $data);
        return $data;
    }
    /**
     * @depends testAccountWidgetInstance
     */
    public function testGenerateOutput(KeysystemsAccountWidget $accountwidget)
    {
        // enabled widget, api data available
        $_SESSION[KeysystemsAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        $balanceObject = new KeysystemsBalance();
        $result = $accountwidget->generateOutput([
            "balance" => $balanceObject,
            "status" => 1,
            "widgetid" => "KeysystemsAccountWidget"
        ]);
        $matcher = "<div class=\"data color-pink\">-16498.09</div>";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "hxStartCounter(`#hxbalexpires" . KeysystemsAccountWidget::$widgetid . "`);";
        $this->assertStringContainsString($matcher, $result);

        // registrar module missing or inactive
        $result = $accountwidget->generateOutput([
            "status" => -1,
            "widgetid" => "KeysystemsAccountWidget"
        ]);
        $matcher = "Please install or upgrade to the latest RRPproxy (Keysystems) Registrar Module.";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "hxStartCounter(\"#hxbalexpires" . KeysystemsAccountWidget::$widgetid . "\");";
        $this->assertStringNotContainsString($matcher, $result);

        // refresh request - main js logics shall not be returned
        $_REQUEST["refresh"] = 1;
        // enabled widget, api data available
        $result = $accountwidget->generateOutput([
            "balance" => $balanceObject,
            "status" => 1,
            "widgetid" => "KeysystemsAccountWidget"
        ]);
        $matcher = "<div class=\"data color-pink\">-16498.09</div>";
        $this->assertStringContainsString($matcher, $result);
        $matcher = "hxStartCounter(\"#hxbalexpires" . KeysystemsAccountWidget::$widgetid . "\");";
        $this->assertStringNotContainsString($matcher, $result);
    }
}
