<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Typst\Compiler;
use Typst\Document;
use Typst\Exception\InvalidArgumentException;
use Typst\Exception\RuntimeException;
use Typst\World;

final class CompilerTest extends TestCase
{
    public function testConstruction(): void
    {
        self::assertInstanceOf(Compiler::class, new Compiler(new World()));
    }

    public function testCompileSimpleSource(): void
    {
        $world = new World();
        $compiler = new Compiler($world);
        $doc = $compiler->compile($world->loadString("#set page(height: auto)\nHello, World!"));
        self::assertInstanceOf(Document::class, $doc);
        self::assertSame(1, $doc->pageCount());
    }

    public function testCompileStringConvenience(): void
    {
        $doc = (new Compiler(new World()))->compileString("#set page(height: auto)\nHello");
        self::assertSame(1, $doc->pageCount());
    }

    public function testCompileInvalidSourceThrows(): void
    {
        $world = new World();
        $compiler = new Compiler($world);
        $this->expectException(RuntimeException::class);
        $compiler->compile($world->loadString('#invalid-function()'));
    }

    public function testCompileWithInputs(): void
    {
        $world = new World();
        $compiler = new Compiler($world);
        $source = $world->loadString("#set page(height: auto)\n#sys.inputs.at(\"name\")");
        $doc = $compiler->compile($source, ['name' => 'Claude']);
        self::assertInstanceOf(Document::class, $doc);
    }

    public function testCompileEmptySource(): void
    {
        $doc = (new Compiler(new World()))->compileString('');
        self::assertInstanceOf(Document::class, $doc);
        self::assertSame(1, $doc->pageCount());
    }

    public function testCompileMultiPageDocument(): void
    {
        $doc = (new Compiler(new World()))->compileString("Page 1\n#pagebreak()\nPage 2\n#pagebreak()\nPage 3");
        self::assertSame(3, $doc->pageCount());
    }

    public function testCompileFileValid(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'typst_test_');
        self::assertNotFalse($tmp);
        file_put_contents($tmp, "#set page(height: auto)\nHello from file");
        try {
            $doc = (new Compiler(new World()))->compileFile($tmp);
            self::assertInstanceOf(Document::class, $doc);
        } finally {
            unlink($tmp);
        }
    }

    public function testCompileFileNonExistentThrows(): void
    {
        $this->expectException(RuntimeException::class);
        (new Compiler(new World()))->compileFile('/nonexistent/path/file.typ');
    }

    public function testCompileFileWithInputs(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'typst_test_');
        self::assertNotFalse($tmp);
        file_put_contents($tmp, "#set page(height: auto)\n#sys.inputs.at(\"key\")");
        try {
            $doc = (new Compiler(new World()))->compileFile($tmp, ['key' => 'value']);
            self::assertInstanceOf(Document::class, $doc);
        } finally {
            unlink($tmp);
        }
    }

    public function testCompileFileInvalidTypstThrows(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'typst_test_');
        self::assertNotFalse($tmp);
        file_put_contents($tmp, '#unknown-func()');
        try {
            $this->expectException(RuntimeException::class);
            (new Compiler(new World()))->compileFile($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    public function testCompileWithTemplateDirResolvesImports(): void
    {
        $dir = sys_get_temp_dir() . '/typst_test_' . uniqid('', true);
        mkdir($dir, 0o777, true);
        file_put_contents($dir . '/lib.typ', '#let greet(name) = [Hello, #name!]');
        file_put_contents(
            $dir . '/main.typ',
            "#import \"lib.typ\": greet\n#set page(height: auto)\n#greet(\"World\")",
        );
        try {
            $world = new World(template_dir: $dir);
            $doc = (new Compiler($world))->compileFile($dir . '/main.typ');
            self::assertInstanceOf(Document::class, $doc);
        } finally {
            unlink($dir . '/lib.typ');
            unlink($dir . '/main.typ');
            rmdir($dir);
        }
    }

    public function testCompileFixtureHello(): void
    {
        $world = new World(template_dir: __DIR__ . '/fixtures');
        $doc = (new Compiler($world))->compileFile(__DIR__ . '/fixtures/hello.typ');
        self::assertSame(1, $doc->pageCount());
    }

    public function testCompileNestedImports(): void
    {
        $world = new World(template_dir: __DIR__ . '/fixtures');
        $doc = (new Compiler($world))->compileString(
            "#import \"nested-a.typ\": compute, make-title\n#set page(height: auto)\n#make-title[Nested]\n#compute(21)",
        );
        self::assertSame(1, $doc->pageCount());
    }

    public function testSourceFromDifferentWorldThrows(): void
    {
        $worldA = new World();
        $worldB = new World();
        $source = $worldA->loadString("#set page(height: auto)\nHi");
        $this->expectException(InvalidArgumentException::class);
        (new Compiler($worldB))->compile($source);
    }

    public function testClearCacheReturnsNonNegative(): void
    {
        $world = new World();
        $compiler = new Compiler($world);
        $compiler->compileString("#set page(height: auto)\nHello");
        self::assertGreaterThanOrEqual(0, $compiler->clearCache());
    }

    public function testGetWorldReturnsClone(): void
    {
        $world = new World(cache_size: 10);
        $compiler = new Compiler($world);
        $got = $compiler->getWorld();
        self::assertInstanceOf(World::class, $got);
        self::assertSame('10', $got->__debugInfo()['cacheSize']);
    }

    public function testCloneProducesIndependentCompiler(): void
    {
        $compiler = new Compiler(new World());
        $clone = clone $compiler;
        $doc = $clone->compileString("#set page(height: auto)\nCloned");
        self::assertSame(1, $doc->pageCount());
    }

    public function testDebugInfoContainsVersions(): void
    {
        $info = (new Compiler(new World()))->__debugInfo();
        self::assertArrayHasKey('version', $info);
        self::assertArrayHasKey('typstVersion', $info);
    }

    public function testEmptyInputsSameAsNull(): void
    {
        $world = new World();
        $compiler = new Compiler($world);
        $source = $world->loadString("#set page(height: auto)\nHello");
        $a = $compiler->compile($source, []);
        $b = $compiler->compile($source, null);
        self::assertSame($a->pageCount(), $b->pageCount());
    }
}
