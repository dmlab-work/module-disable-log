<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Plugin\Customer;

use DmLab\DisableLog\Model\Config;
use DmLab\DisableLog\Plugin\Customer\SkipVisitorLog;
use Magento\Customer\Model\ResourceModel\Visitor as VisitorResource;
use Magento\Customer\Model\Visitor;
use PHPUnit\Framework\TestCase;

class SkipVisitorLogTest extends TestCase
{
    public function testSkipsWriteWhenDisabled(): void
    {
        $subject = self::createStub(VisitorResource::class);
        $plugin = new SkipVisitorLog($this->config(true));

        $result = $plugin->aroundSave(
            $subject,
            static function (): never {
                self::fail('customer_visitor write must be skipped');
            },
            self::createStub(Visitor::class)
        );

        self::assertSame($subject, $result);
    }

    public function testWritesWhenEnabled(): void
    {
        $subject = self::createStub(VisitorResource::class);
        $proceedResult = self::createStub(VisitorResource::class);
        $visitor = self::createStub(Visitor::class);
        $calls = [];
        $plugin = new SkipVisitorLog($this->config(false));

        $result = $plugin->aroundSave(
            $subject,
            static function (Visitor $passed) use (&$calls, $proceedResult): VisitorResource {
                $calls[] = $passed;
                return $proceedResult;
            },
            $visitor
        );

        self::assertSame([$visitor], $calls);
        self::assertSame($proceedResult, $result);
    }

    public function testPropagatesCoreExceptionWhenEnabled(): void
    {
        $plugin = new SkipVisitorLog($this->config(false));

        $this->expectException(\RuntimeException::class);
        $plugin->aroundSave(
            self::createStub(VisitorResource::class),
            static function (): never {
                throw new \RuntimeException('DB write failed');
            },
            self::createStub(Visitor::class)
        );
    }

    public function testDiRegistersPluginGlobally(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/di.xml');
        self::assertNotFalse($xml);

        $nodes = $xml->xpath("/config/type[@name='Magento\\Customer\\Model\\ResourceModel\\Visitor']/plugin");

        self::assertCount(1, $nodes);
        self::assertSame(SkipVisitorLog::class, (string)$nodes[0]['type']);
    }

    private function config(bool $disabled): Config
    {
        $config = self::createStub(Config::class);
        $config->method('isVisitorLogDisabled')->willReturn($disabled);
        return $config;
    }
}
