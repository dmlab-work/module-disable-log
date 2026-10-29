<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Model;

use DmLab\DisableLog\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function toggles(): array
    {
        return [
            'customer' => [Config::XML_PATH_DISABLE_CUSTOMER_LOG, 'isCustomerLogDisabled'],
            'visitor' => [Config::XML_PATH_DISABLE_VISITOR_LOG, 'isVisitorLogDisabled'],
            'search' => [Config::XML_PATH_DISABLE_SEARCH_LOG, 'isSearchLogDisabled'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function paths(): array
    {
        return array_map(static fn (array $toggle): array => [$toggle[0]], self::toggles());
    }

    #[DataProvider('toggles')]
    public function testToggleReadsOnlyItsOwnPath(string $path, string $method): void
    {
        $scopeConfig = self::createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn (string $requested): bool => $requested === $path
        );
        $config = new Config($scopeConfig);

        foreach (self::toggles() as [$otherPath, $otherMethod]) {
            self::assertSame($otherPath === $path, $config->$otherMethod(), $otherMethod);
        }
    }

    public function testAllTogglesOffWhenUnset(): void
    {
        $scopeConfig = self::createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(false);
        $config = new Config($scopeConfig);

        self::assertFalse($config->isCustomerLogDisabled());
        self::assertFalse($config->isVisitorLogDisabled());
        self::assertFalse($config->isSearchLogDisabled());
    }

    #[DataProvider('paths')]
    public function testShippedDefaultKeepsLogging(string $path): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 3) . '/etc/config.xml');
        self::assertNotFalse($xml);

        [$section, $group, $field] = explode('/', $path);
        $value = $xml->default->{$section}->{$group}->{$field};

        self::assertCount(1, $value, $path);
        self::assertSame('0', (string)$value);
    }

    #[DataProvider('paths')]
    public function testSystemXmlDeclaresYesNoField(string $path): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 3) . '/etc/adminhtml/system.xml');
        self::assertNotFalse($xml);

        [$section, $group, $field] = explode('/', $path);
        $nodes = $xml->xpath(sprintf(
            "/config/system/section[@id='%s'][tab='dmlab']/group[@id='%s']/field[@id='%s']",
            $section,
            $group,
            $field
        ));

        self::assertCount(1, $nodes, $path);
        self::assertSame('Magento\Config\Model\Config\Source\Yesno', (string)$nodes[0]->source_model);
    }
}
