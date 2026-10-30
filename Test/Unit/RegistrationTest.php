<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase
{
    public function testModuleIsRegistered(): void
    {
        $paths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);

        self::assertArrayHasKey('DmLab_DisableLog', $paths);
        self::assertFileExists($paths['DmLab_DisableLog'] . '/etc/module.xml');
    }

    public function testModuleXmlDeclaresVersionAndSequence(): void
    {
        $xml = simplexml_load_file(self::moduleRoot() . '/etc/module.xml');

        self::assertNotFalse($xml);
        self::assertSame('DmLab_DisableLog', (string)$xml->module['name']);
        self::assertSame('0.0.1', (string)$xml->module['setup_version']);

        $sequence = [];
        foreach ($xml->module->sequence->module as $module) {
            $sequence[] = (string)$module['name'];
        }
        self::assertSame(['Magento_Customer', 'Magento_Reports', 'Magento_Search'], $sequence);
    }

    public function testComposerManifest(): void
    {
        $composer = json_decode(
            (string)file_get_contents(self::moduleRoot() . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $require = array_keys($composer['require']);
        sort($require);

        self::assertSame('dmlab/module-disable-log', $composer['name']);
        self::assertSame('magento2-module', $composer['type']);
        self::assertSame('OSL-3.0', $composer['license']);
        self::assertSame('0.0.2', $composer['version']);
        self::assertSame(
            ['magento/framework', 'magento/module-config', 'magento/module-customer',
                'magento/module-reports', 'magento/module-search', 'php'],
            $require
        );
        self::assertSame('', $composer['autoload']['psr-4']['DmLab\\DisableLog\\']);
    }

    private static function moduleRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
