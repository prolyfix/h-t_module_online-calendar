<?php

declare(strict_types=1);

/**
 * Lightweight module-level test that can run without dev dependencies.
 */
final class BundleMetadataTest
{
    public static function run(): void
    {
        $moduleRoot = dirname(__DIR__);
        $composerPath = $moduleRoot . '/composer.json';
        $composerRaw = file_get_contents($composerPath);

        if ($composerRaw === false) {
            self::fail('Unable to read composer.json');
        }

        $composer = json_decode($composerRaw, true);
        if (!is_array($composer)) {
            self::fail('composer.json is not valid JSON');
        }

        $autoload = $composer['autoload']['psr-4'] ?? null;
        if (!is_array($autoload) || $autoload === []) {
            self::fail('Missing autoload.psr-4 in composer.json');
        }

        $namespacePrefix = array_key_first($autoload);
        if (!is_string($namespacePrefix) || $namespacePrefix === '') {
            self::fail('Could not determine namespace prefix from autoload.psr-4');
        }

        $bundleFiles = glob($moduleRoot . '/src/*Bundle.php') ?: [];
        if (count($bundleFiles) !== 1) {
            self::fail('Expected exactly one *Bundle.php file in src/');
        }

        $bundleSource = file_get_contents($bundleFiles[0]);
        if ($bundleSource === false) {
            self::fail('Unable to read bundle source file');
        }

        $expectedNamespace = 'namespace ' . rtrim($namespacePrefix, '\\') . ';';
        if (strpos($bundleSource, $expectedNamespace) === false) {
            self::fail('Bundle namespace does not match composer autoload namespace');
        }

        if (!preg_match('/class\\s+\\w+Bundle\\s+extends\\s+\\w*Bundle/', $bundleSource)) {
            self::fail('Bundle class does not extend a *Bundle base class');
        }

        echo "Bundle metadata checks passed\n";
    }

    private static function fail(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

BundleMetadataTest::run();
