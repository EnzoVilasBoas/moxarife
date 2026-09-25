<?php

declare(strict_types=1);

namespace Moxarife\Api\Tests;

use Moxarife\Api\Config\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function testReadsValuesFromFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'moxarife-env-');
        self::assertIsString($path);
        file_put_contents($path, "FOO=bar\n# comment\nEMPTY=\n");

        try {
            $environment = Environment::fromFile($path);
            self::assertSame('bar', $environment->get('FOO'));
            self::assertSame('', $environment->get('EMPTY'));
        } finally {
            unlink($path);
        }
    }

    public function testMissingRequiredValueFails(): void
    {
        $environment = new Environment([]);

        $this->expectException(\RuntimeException::class);
        $environment->get('JWT_SECRET');
    }
}
