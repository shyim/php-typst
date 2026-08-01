<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testLibraryVersionIsNonEmpty(): void
    {
        $version = \Typst\version();
        self::assertNotSame('', $version);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function testEngineVersionIsKnown(): void
    {
        $version = \Typst\typst_version();
        self::assertNotSame('unknown', $version);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }
}
