<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Typst\Compiler;
use Typst\Native;
use Typst\World;

final class HelloTest extends TestCase
{
    public function testNativeLibraryIsInstalled(): void
    {
        $path = Native::discoverLibraryPath();
        self::assertFileExists($path);
    }

    public function testCompileStringToAllFormats(): void
    {
        $document = (new Compiler(new World()))->compileString(<<<'TYPST'
            #set page(height: auto)
            = Hello
            TYPST);

        self::assertGreaterThanOrEqual(1, $document->pageCount());
        self::assertStringStartsWith('%PDF', $document->toPdf()->bytes());
        self::assertStringStartsWith("\x89PNG", $document->toImage()->bytes());
        self::assertStringContainsString('<svg', $document->toSvg()->bytes());
    }

    public function testVersions(): void
    {
        self::assertNotSame('', \Typst\version());
        self::assertNotSame('unknown', \Typst\typst_version());
    }
}
