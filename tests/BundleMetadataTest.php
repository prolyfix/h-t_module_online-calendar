<?php

declare(strict_types=1);

namespace Prolyfix\OnlineCalendarBundle\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Lightweight module-level test that can run without dev dependencies.
 */
final class BundleMetadataTest extends TestCase
{
    public function testBundleMetadata(): void
    {
        $moduleRoot = dirname(__DIR__);
        $composerPath = $moduleRoot . '/composer.json';
        $composerRaw = file_get_contents($composerPath);
        $this->assertNotFalse($composerRaw, 'Unable to read composer.json');

        $composer = json_decode($composerRaw, true);
        $this->assertIsArray($composer, 'composer.json is not valid JSON');

        $autoload = $composer['autoload']['psr-4'] ?? null;
        $this->assertNotEmpty($autoload, 'Missing autoload.psr-4 in composer.json');

        $namespacePrefix = array_key_first($autoload);
        $this->assertIsString($namespacePrefix, 'Could not determine namespace prefix from autoload.psr-4');
        $this->assertNotSame('', $namespacePrefix, 'Could not determine namespace prefix from autoload.psr-4');

        $bundleFiles = glob($moduleRoot . '/src/*Bundle.php') ?: [];
        $this->assertCount(1, $bundleFiles, 'Expected exactly one *Bundle.php file in src/');

        $bundleSource = file_get_contents($bundleFiles[0]);
        $this->assertNotFalse($bundleSource, 'Unable to read bundle source file');

        $expectedNamespace = 'namespace ' . rtrim($namespacePrefix, '\\') . ';';
        $this->assertStringContainsString($expectedNamespace, $bundleSource, 'Bundle namespace does not match composer autoload namespace');

        $this->assertMatchesRegularExpression('/class\\s+\\w+Bundle\\s+extends\\s+\\w*Bundle/', $bundleSource, 'Bundle class does not extend a *Bundle base class');
    }
}
