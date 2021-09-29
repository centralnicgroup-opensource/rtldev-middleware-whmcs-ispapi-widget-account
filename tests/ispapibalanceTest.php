<?php

namespace WHMCS\Module\Widget;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\Ispapi\Ispapi;

final class IspapiBalanceTest extends TestCase
{
    /**
     * @cover IspapiBalance __construct
     */
    public function testBalanceInstance()
    {
        $balance = new IspapiBalance();
        $this->assertInstanceOf('WHMCS\Module\Widget\IspapiBalance', $balance);
        return $balance;
    }

    /**
     * @depends testBalanceInstance
     */
    public function testGetData(IspapiBalance $balance)
    {
        // case data exist
        $data = $balance->getData();
        $this->assertArrayHasKey('amount', $data);
        $this->assertArrayHasKey('deposit', $data);
        $this->assertArrayHasKey('fundsav', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('currencyID', $data);
        $this->assertArrayHasKey('hasDeposits', $data);
        $this->assertArrayHasKey('isOverdraft', $data);
        // case no data
        $data = [];
    }

    public function testToHTML()
    {
        // case null data
        unset($_SESSION[IspapiAccountWidget::$widgetid]);
        Ispapi::$apiCase = 0;
        $balance = new IspapiBalance();
        $result = $balance->toHTML();
        $matcher = "<div class=\"color-pink\">Loading Account Data failed.</div>";
        $this->assertStringContainsString($matcher, $result);
        // case no deposit
        $_SESSION[IspapiAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        Ispapi::$apiCase = 2;
        $balance = new IspapiBalance();
        $result = $balance->toHTML();
        $matcher = "<div class=\"note\">Account Balance</div>";
        $this->assertStringContainsString($matcher, $result);
        // default
        $_SESSION[IspapiAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        Ispapi::$apiCase = 3;
        $balance = new IspapiBalance();
        $result = $balance->toHTML();
        $matcher = "<div class=\"note\">Available Funds</div>";
        $this->assertStringContainsString($matcher, $result);
    }

    public function testGetDataFormatted()
    {
        // case null data
        unset($_SESSION[IspapiAccountWidget::$widgetid]);
        Ispapi::$apiCase = 0;
        $balance = new IspapiBalance();
        $result = $balance->getDataFormatted();
        $this->assertNull($result);
        // case null currencyID
        // 1 = RESPONSE_BALANCE_NO_DEPOSITS_OVERDRAFT
        $_SESSION[IspapiAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        Ispapi::$apiCase = 1;
        $balance = new IspapiBalance();
        $result = $balance->getDataFormatted();
        $this->assertTrue($result['isOverdraft']);
        // 2 = RESPONSE_BALANCE_NO_DEPOSITS_NO_OVERDRAFT
        $_SESSION[IspapiAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        Ispapi::$apiCase = 2;
        $balance = new IspapiBalance();
        $result = $balance->getDataFormatted();
        $this->assertFalse($result['isOverdraft'], $result['hasDeposits']);
        // 3 = RESPONSE_BALANCE_WITH_DEPOSITS_OVERDRAFT
        $_SESSION[IspapiAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        Ispapi::$apiCase = 3;
        $balance = new IspapiBalance();
        $result = $balance->getDataFormatted();
        $this->assertTrue($result['isOverdraft'], $result['hasDeposits']);
        // 4 = RESPONSE_BALANCE_WITH_DEPOSITS_NO_OVERDRAFT
        $_SESSION[IspapiAccountWidget::$widgetid] = [
            "expires" => 3600,
            "ttl" => 3600
        ];
        Ispapi::$apiCase = 4;
        $balance = new IspapiBalance();
        $result = $balance->getDataFormatted();
        $this->assertTrue($result['hasDeposits']);
        $this->assertFalse($result['isOverdraft']);
    }
}
