<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Plugin\Customer;

use DmLab\DisableLog\Model\Config;
use DmLab\DisableLog\Plugin\Customer\SkipCustomerLog;
use Magento\Customer\Model\Logger;
use PHPUnit\Framework\TestCase;

class SkipCustomerLogTest extends TestCase
{
    public function testSkipsWriteWhenDisabled(): void
    {
        $subject = self::createStub(Logger::class);
        $plugin = new SkipCustomerLog($this->config(true));

        $result = $plugin->aroundLog(
            $subject,
            static function (): never {
                self::fail('customer_log write must be skipped');
            },
            7,
            ['last_login_at' => '2026-10-21 10:00:00']
        );

        self::assertSame($subject, $result);
    }

    public function testWritesWhenEnabled(): void
    {
        $subject = self::createStub(Logger::class);
        $proceedResult = self::createStub(Logger::class);
        $data = ['last_logout_at' => '2026-10-21 10:00:00'];
        $calls = [];
        $plugin = new SkipCustomerLog($this->config(false));

        $result = $plugin->aroundLog(
            $subject,
            static function (int $customerId, array $passed) use (&$calls, $proceedResult): Logger {
                $calls[] = [$customerId, $passed];
                return $proceedResult;
            },
            7,
            $data
        );

        self::assertSame([[7, $data]], $calls);
        self::assertSame($proceedResult, $result);
    }

    public function testPropagatesCoreExceptionWhenEnabled(): void
    {
        $plugin = new SkipCustomerLog($this->config(false));

        $this->expectException(\InvalidArgumentException::class);
        $plugin->aroundLog(
            self::createStub(Logger::class),
            static function (): never {
                throw new \InvalidArgumentException('Log data is empty');
            },
            7,
            []
        );
    }

    public function testDiRegistersPluginGlobally(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/di.xml');
        self::assertNotFalse($xml);

        $nodes = $xml->xpath("/config/type[@name='Magento\\Customer\\Model\\Logger']/plugin");

        self::assertCount(1, $nodes);
        self::assertSame(SkipCustomerLog::class, (string)$nodes[0]['type']);
    }

    private function config(bool $disabled): Config
    {
        $config = self::createStub(Config::class);
        $config->method('isCustomerLogDisabled')->willReturn($disabled);
        return $config;
    }
}
