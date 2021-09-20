<?php

namespace WHMCS\Module\Widget;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\Ispapi\Ispapi;

final class IspapiStatisticsTest extends TestCase
{
    /**
     * @cover IspapiStatistics __construct
    */
    public function testStatisticsInstance()
    {
        $statistics = new IspapiStatistics();
        // whmcs\\module\\widget\\ispapicurrency
        $this->assertInstanceOf('WHMCS\Module\Widget\IspapiStatistics', $statistics);
        return $statistics;
    }
    /**
     * @cover magic getter
     * @depends testStatisticsInstance
     */
    public function testMagicMethods(IspapiStatistics $statistics)
    {
        // __get - null case
        $this->assertNull($statistics->__get("key"));
        // __set
        $statistics->__set("key", "value");
        // __get - test previouse set value
        $this->assertEquals($statistics->__get("key"), "value");
        // __isset
        $this->assertTrue($statistics->__isset("key"), true);
        // __unset
        $statistics->__unset("key");
        // __get - test unset
        $this->assertNull($statistics->__get("key"));
    }
    /**
     * @cover toHTML
     * @depends testStatisticsInstance
     */
    public function testToHTML(Ispapistatistics $statistics)
    {
        // case empty data
        $result = $statistics->toHTML();
        $matcher = ISPAPI_LOGO_URL;
        $this->assertStringContainsString($matcher, $result);
        // case there is data
        $statistics->__set("productTitle", "val");
        $result = $statistics->toHTML();
        $matcher = "<div class=\"col-xs-9 col-sm-8\">productTitle</div>";
        $this->assertStringContainsString($matcher, $result);
    }
}
