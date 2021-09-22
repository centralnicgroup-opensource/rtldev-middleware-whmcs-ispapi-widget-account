<?php

namespace WHMCS\Module\Widget;

use PHPUnit\Framework\TestCase;

final class IspapiBaseWidgetTest extends TestCase
{
    /**
     * @cover IspapiBaseWidget __construct
     */
    public function testBaseWidgetInstance()
    {
        $widget = new IspapiBaseWidget("ispapiAccountWidget");
        $this->assertInstanceOf("WHMCS\Module\Widget\IspapiBaseWidget", $widget);
        return $widget;
    }
    /**
     * @depends testBaseWidgetInstance
     */
    public function testGetData(IspapiBaseWidget $widget)
    {
        // case widget disabled
        \WHMCS\Config\Setting::setValue("ispapiAccountWidget", 0);
        $data = $widget->getData();
        $this->assertArrayHasKey("status", $data);
        $this->assertEquals($data["status"], 0);
        $this->assertEquals(count($data), 1);
        // case widget enabled
        \WHMCS\Config\Setting::setValue("ispapiAccountWidget", 1);
        $data = $widget->getData();
        $this->assertArrayHasKey("status", $data);
        $this->assertEquals($data["status"], 1);
        $this->assertEquals(count($data), 1);
        $_REQUEST["status"] = 0;
        $data = $widget->getData();
        $this->assertArrayHasKey("success", $data);
        $this->assertEquals($data["success"], true);
        $this->assertEquals(count($data), 1);
    }

    /**
     * @depends testBaseWidgetInstance
     */
    public function testGenerateOutput(IspapiBaseWidget $widget)
    {
        $_REQUEST = [];
        // disabled widget
        $output = $widget->generateOutput(["status" => 0]);
        $matcher = "Widget is currently disabled. Use the first icon for enabling.";
        $this->assertStringContainsString($matcher, $output);

        // missing registrar module
        $output = $widget->generateOutput(["status" => -1]);
        $matcher = "Please install or upgrade to the latest HEXONET ISPAPI Registrar Module.";
        $this->assertStringContainsString($matcher, $output);

        // enabled widget
        $output = $widget->generateOutput(["status" => 1]);
        $matcher = "Widget is currently disabled. Use the first icon for enabling.";
        $this->assertStringNotContainsString($matcher, $output);
        $matcher = "Please install or upgrade to the latest HEXONET ISPAPI Registrar Module.";
        $this->assertStringNotContainsString($matcher, $output);
    }
}
