<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 *
 * Standalone unit-test bootstrap: loads Magento's Composer autoloader (unless PHPUnit already did),
 * registers a PSR-4 map for this module so it resolves without a `composer install`, then registers it.
 */
declare(strict_types=1);

$moduleRoot = dirname(__DIR__, 2);

$candidates = [
    getenv('MAGENTO_ROOT') ? rtrim((string)getenv('MAGENTO_ROOT'), '/') . '/vendor/autoload.php' : null,
    // Composer install: <root>/vendor/dmlab/module-disable-log.
    $moduleRoot . '/../../autoload.php',
    '/var/www/html/vendor/autoload.php',
    $moduleRoot . '/vendor/autoload.php',
    $moduleRoot . '/../../src/vendor/autoload.php',
];

$autoloaderLoaded = class_exists(\Magento\Framework\Component\ComponentRegistrar::class);
foreach ($autoloaderLoaded ? [] : $candidates as $candidate) {
    if ($candidate !== null && is_file($candidate)) {
        require $candidate;
        $autoloaderLoaded = true;
        break;
    }
}

if (!$autoloaderLoaded) {
    fwrite(STDERR, "Unable to locate a Composer autoloader (set MAGENTO_ROOT).\n");
    // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
    exit(1);
}

$prefix = 'DmLab\\DisableLog\\';

spl_autoload_register(static function (string $class) use ($moduleRoot, $prefix): void {
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $moduleRoot . '/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Already registered when the module is composer-installed; re-registering throws.
try {
    require $moduleRoot . '/registration.php';
} catch (\LogicException $e) {
    // Already registered by the Composer autoloader.
}
