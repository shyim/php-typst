<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Typst\Compiler;
use Typst\Exception\InvalidArgumentException;
use Typst\Exception\RuntimeException;
use Typst\Source;
use Typst\World;

final class WorldTest extends TestCase
{
    public function testDefaultConstruction(): void
    {
        self::assertInstanceOf(World::class, new World());
    }

    public function testConstructionWithTemplateDir(): void
    {
        self::assertInstanceOf(World::class, new World(template_dir: __DIR__ . '/fixtures'));
    }

    public function testConstructionWithCacheSize(): void
    {
        self::assertInstanceOf(World::class, new World(cache_size: 128));
    }

    public function testConstructionWithCacheSizeZero(): void
    {
        self::assertInstanceOf(World::class, new World(cache_size: 0));
    }

    public function testConstructionNegativeCacheSizeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new World(cache_size: -1);
    }

    public function testConstructionWithoutDefaultFonts(): void
    {
        self::assertInstanceOf(World::class, new World(embed_default_fonts: false));
    }

    public function testConstructionWithFontDirs(): void
    {
        self::assertInstanceOf(World::class, new World(font_dirs: [__DIR__ . '/fixtures/fonts']));
    }

    public function testConstructionWithInvalidFontDirThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new World(font_dirs: ['/nonexistent/font-dir']);
    }

    public function testLoadStringReturnsSource(): void
    {
        $source = (new World())->loadString("#set page(height: auto)\nHello");
        self::assertInstanceOf(Source::class, $source);
    }

    public function testLoadStringEmptySource(): void
    {
        $source = (new World())->loadString('');
        self::assertInstanceOf(Source::class, $source);
        self::assertSame('', $source->getText());
    }

    public function testLoadStringTextPreserved(): void
    {
        $text = "#set page(height: auto)\nHello, World!";
        $source = (new World())->loadString($text);
        self::assertSame($text, $source->getText());
    }

    public function testLoadStringReturnsUniqueIds(): void
    {
        $world = new World();
        $s1 = $world->loadString('Hello');
        $s2 = $world->loadString('World');
        self::assertNotSame($s1->getId(), $s2->getId());
    }

    public function testLoadFileReturnsSource(): void
    {
        $source = (new World())->loadFile(__DIR__ . '/fixtures/hello.typ');
        self::assertInstanceOf(Source::class, $source);
    }

    public function testLoadFileTextMatchesFileContent(): void
    {
        $source = (new World())->loadFile(__DIR__ . '/fixtures/hello.typ');
        $expected = file_get_contents(__DIR__ . '/fixtures/hello.typ');
        self::assertSame($expected, $source->getText());
    }

    public function testLoadFileNonExistentThrows(): void
    {
        $this->expectException(RuntimeException::class);
        (new World())->loadFile('/nonexistent/path/file.typ');
    }

    public function testSourceIdIsNonNegativeInt(): void
    {
        $id = (new World())->loadString('Hello')->getId();
        self::assertGreaterThanOrEqual(0, $id);
    }

    public function testCloneSharesLineageForSources(): void
    {
        $world = new World();
        $clone = clone $world;
        $source = $world->loadString("#set page(height: auto)\nHello");
        $doc = (new Compiler($clone))->compile($source);
        self::assertSame(1, $doc->pageCount());
    }

    public function testGetFontFamiliesIncludesDefaults(): void
    {
        $families = (new World())->getFontFamilies();
        self::assertNotEmpty($families);
        foreach ($families as $family) {
            self::assertNotSame('', $family);
        }
    }

    public function testAddFontFileValid(): void
    {
        $world = new World();
        $world->addFontFile(__DIR__ . '/fixtures/fonts/Roboto-Regular.ttf');
        $doc = (new Compiler($world))->compileString("#set page(height: auto)\nHello with custom font");
        self::assertSame(1, $doc->pageCount());
    }

    public function testAddFontFileNonExistentThrows(): void
    {
        $this->expectException(RuntimeException::class);
        (new World())->addFontFile('/nonexistent/font.ttf');
    }

    public function testAddFontDataValid(): void
    {
        $data = file_get_contents(__DIR__ . '/fixtures/fonts/Roboto-Regular.ttf');
        self::assertNotFalse($data);

        $world = new World();
        $world->addFontData($data);
        self::assertNotEmpty($world->getFontFamilies());
    }

    public function testAddFontDataInvalidThrows(): void
    {
        $this->expectException(RuntimeException::class);
        (new World())->addFontData('not a font');
    }

    public function testDebugInfoContainsKeys(): void
    {
        $info = (new World(template_dir: __DIR__ . '/fixtures', cache_size: 32))->__debugInfo();
        self::assertArrayHasKey('templateDir', $info);
        self::assertArrayHasKey('cacheSize', $info);
        self::assertSame('32', $info['cacheSize']);
    }
}
