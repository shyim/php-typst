<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Typst\Compiler;
use Typst\Document;
use Typst\Exception\LogicException;
use Typst\Exception\RuntimeException;
use Typst\PendingDocument;
use Typst\World;

final class PendingDocumentTest extends TestCase
{
    private World $world;
    private Compiler $compiler;

    protected function setUp(): void
    {
        $this->world = new World();
        $this->compiler = new Compiler($this->world);
    }

    public function testCompileInBackgroundReturnsPendingDocument(): void
    {
        $source = $this->world->loadString("#set page(height: auto)\nHello");
        $pending = $this->compiler->compileInBackground($source);
        self::assertInstanceOf(PendingDocument::class, $pending);
    }

    public function testJoinReturnsDocument(): void
    {
        $source = $this->world->loadString("#set page(height: auto)\nHello");
        $document = $this->compiler->compileInBackground($source)->join();
        self::assertInstanceOf(Document::class, $document);
        self::assertSame(1, $document->pageCount());
        self::assertStringStartsWith('%PDF', $document->toPdf()->bytes());
    }

    public function testJoinProducesComparableResultAsCompile(): void
    {
        $source = $this->world->loadString("#set page(height: auto)\nHello, World!");
        $syncDoc = $this->compiler->compile($source);
        $asyncDoc = $this->compiler->compileInBackground($source)->join();
        self::assertSame($syncDoc->pageCount(), $asyncDoc->pageCount());
        self::assertSame(strlen((string) $syncDoc->toPdf()), strlen((string) $asyncDoc->toPdf()));
    }

    public function testJoinTwiceThrowsLogicException(): void
    {
        $source = $this->world->loadString("#set page(height: auto)\nHello");
        $pending = $this->compiler->compileInBackground($source);
        $pending->join();
        $this->expectException(LogicException::class);
        $pending->join();
    }

    public function testGetNotificationStreamReturnsResource(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('Notification streams are Unix-only');
        }

        $source = $this->world->loadString("#set page(height: auto)\nHello");
        $pending = $this->compiler->compileInBackground($source);
        $stream = $pending->getNotificationStream();
        self::assertTrue(is_resource($stream));
        $pending->join();
    }

    public function testGetNotificationStreamAfterJoinThrows(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('Notification streams are Unix-only');
        }

        $source = $this->world->loadString("#set page(height: auto)\nHello");
        $pending = $this->compiler->compileInBackground($source);
        $pending->join();
        $this->expectException(LogicException::class);
        $pending->getNotificationStream();
    }

    public function testIsReadyEventuallyTrue(): void
    {
        $source = $this->world->loadString("#set page(height: auto)\nHello");
        $pending = $this->compiler->compileInBackground($source);
        $pending->join();
        self::assertTrue($pending->isReady());
    }

    public function testBackgroundCompilationFailureThrowsOnJoin(): void
    {
        $source = $this->world->loadString('#unknown-func()');
        $pending = $this->compiler->compileInBackground($source);
        $this->expectException(RuntimeException::class);
        $pending->join();
    }
}
