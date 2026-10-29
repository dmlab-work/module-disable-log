<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Plugin\Reports;

use DmLab\DisableLog\Model\Config;
use DmLab\DisableLog\Plugin\Reports\SkipAnonymousProductIndex;
use Magento\Framework\Model\AbstractModel;
use Magento\Reports\Model\Product\Index\Viewed;
use Magento\Reports\Model\ResourceModel\Product\Index\AbstractIndex;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SkipAnonymousProductIndexTest extends TestCase
{
    public function testSkipsUnownedRowWhenVisitorLogDisabled(): void
    {
        $subject = self::createStub(AbstractIndex::class);
        $plugin = new SkipAnonymousProductIndex($this->config(true));

        $result = $plugin->aroundSave(
            $subject,
            static function (): never {
                self::fail('unowned product-index row must be skipped');
            },
            $this->index(null, null)
        );

        self::assertSame($subject, $result);
    }

    /**
     * @return array<string, array{bool, ?int, ?int}>
     */
    public static function writtenRows(): array
    {
        return [
            'tracking on, guest without visitor' => [false, null, null],
            'tracking on, guest visitor' => [false, 3, null],
            'tracking off, customer' => [true, null, 7],
            'tracking off, legacy visitor' => [true, 3, null],
        ];
    }

    #[DataProvider('writtenRows')]
    public function testWritesRow(bool $disabled, ?int $visitorId, ?int $customerId): void
    {
        $subject = self::createStub(AbstractIndex::class);
        $proceedResult = self::createStub(AbstractIndex::class);
        $index = $this->index($visitorId, $customerId);
        $calls = [];
        $plugin = new SkipAnonymousProductIndex($this->config($disabled));

        $result = $plugin->aroundSave(
            $subject,
            static function (AbstractModel $passed) use (&$calls, $proceedResult): AbstractIndex {
                $calls[] = $passed;
                return $proceedResult;
            },
            $index
        );

        self::assertSame([$index], $calls);
        self::assertSame($proceedResult, $result);
    }

    public function testDiRegistersPluginGlobally(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/di.xml');
        self::assertNotFalse($xml);

        $nodes = $xml->xpath(
            "/config/type[@name='Magento\\Reports\\Model\\ResourceModel\\Product\\Index\\AbstractIndex']/plugin"
        );

        self::assertCount(1, $nodes);
        self::assertSame(SkipAnonymousProductIndex::class, (string)$nodes[0]['type']);
    }

    private function index(?int $visitorId, ?int $customerId): Viewed
    {
        $index = self::createStub(Viewed::class);
        $index->method('getVisitorId')->willReturn($visitorId);
        $index->method('getCustomerId')->willReturn($customerId);
        return $index;
    }

    private function config(bool $disabled): Config
    {
        $config = self::createStub(Config::class);
        $config->method('isVisitorLogDisabled')->willReturn($disabled);
        return $config;
    }
}
