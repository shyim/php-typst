<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Stringable;
use Typst\Diagnostic\CompilationResult;
use Typst\Diagnostic\Diagnostic;
use Typst\Diagnostic\Severity;
use Typst\Document;
use Typst\Exception\InvalidArgumentException;
use Typst\Inspector;
use Typst\World;

final class InspectTest extends TestCase
{
    public function testInspectSuccessful(): void
    {
        $world = new World();
        $result = (new Inspector($world))->inspect($world->loadString("#set page(height: auto)\nHello"));
        self::assertInstanceOf(CompilationResult::class, $result);
        self::assertTrue($result->success());
        self::assertFalse($result->hasErrors());
        self::assertFalse($result->hasWarnings());
    }

    public function testInspectFailedReturnsErrors(): void
    {
        $world = new World();
        $result = (new Inspector($world))->inspect($world->loadString('#unknown-func()'));
        self::assertFalse($result->success());
        self::assertTrue($result->hasErrors());
        self::assertGreaterThan(0, count($result->errors()));
        self::assertNull($result->getDocument());
    }

    public function testInspectWithInputs(): void
    {
        $world = new World();
        $source = $world->loadString("#set page(height: auto)\n#sys.inputs.at(\"x\")");
        $result = (new Inspector($world))->inspect($source, ['x' => 'test']);
        self::assertTrue($result->success());
    }

    public function testInspectStringConvenience(): void
    {
        $result = (new Inspector(new World()))->inspectString("#set page(height: auto)\nHi");
        self::assertTrue($result->success());
        self::assertInstanceOf(Document::class, $result->getDocument());
    }

    public function testInspectFileValid(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'typst_test_');
        self::assertNotFalse($tmp);
        file_put_contents($tmp, "#set page(height: auto)\nHello");
        try {
            $result = (new Inspector(new World()))->inspectFile($tmp);
            self::assertTrue($result->success());
        } finally {
            unlink($tmp);
        }
    }

    public function testInspectFileInvalidReturnsErrors(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'typst_test_');
        self::assertNotFalse($tmp);
        file_put_contents($tmp, '#unknown-func()');
        try {
            $result = (new Inspector(new World()))->inspectFile($tmp);
            self::assertFalse($result->success());
            self::assertTrue($result->hasErrors());
        } finally {
            unlink($tmp);
        }
    }

    public function testGetDocumentReturnsDocument(): void
    {
        $world = new World();
        $result = (new Inspector($world))->inspectString("#set page(height: auto)\nHello");
        $doc = $result->getDocument();
        self::assertInstanceOf(Document::class, $doc);
        self::assertSame(1, $doc->pageCount());
    }

    public function testGetDocumentCanBeCalledMultipleTimes(): void
    {
        $world = new World();
        $result = (new Inspector($world))->inspectString("#set page(height: auto)\nHello");
        $doc1 = $result->getDocument();
        $doc2 = $result->getDocument();
        self::assertInstanceOf(Document::class, $doc1);
        self::assertInstanceOf(Document::class, $doc2);
        self::assertSame($doc1->pageCount(), $doc2->pageCount());
    }

    public function testDiagnosticsOnFailure(): void
    {
        $world = new World();
        $result = (new Inspector($world))->inspectString('#unknown-func()');
        $errors = $result->errors();
        self::assertGreaterThan(0, count($errors));

        $diag = $errors[0];
        self::assertInstanceOf(Diagnostic::class, $diag);
        self::assertSame(Severity::Error, $diag->severity());
        self::assertNotEmpty($diag->message());
        self::assertInstanceOf(Stringable::class, $diag);
        self::assertStringContainsString('error:', (string) $diag);
    }

    public function testDiagnosticsIncludesBothErrorsAndWarnings(): void
    {
        $world = new World();
        $result = (new Inspector($world))->inspectString('#unknown-func()');
        $all = $result->diagnostics();
        self::assertCount(count($result->errors()) + count($result->warnings()), $all);
    }

    public function testDiagnosticMayHaveSpan(): void
    {
        $world = new World();
        $result = (new Inspector($world))->inspectString("#set page(height: auto)\n#unknown-func()");
        $errors = $result->errors();
        self::assertNotEmpty($errors);
        $span = $errors[0]->span();
        if ($span !== null) {
            self::assertGreaterThan(0, $span->line());
            self::assertGreaterThan(0, $span->column());
            self::assertNotSame('', $span->file() . $span->text());
        }
    }

    public function testSourceFromDifferentWorldThrows(): void
    {
        $worldA = new World();
        $worldB = new World();
        $source = $worldA->loadString('x');
        $this->expectException(InvalidArgumentException::class);
        (new Inspector($worldB))->inspect($source);
    }

    public function testClearCacheAndGetWorld(): void
    {
        $world = new World(cache_size: 8);
        $inspector = new Inspector($world);
        $inspector->inspectString("#set page(height: auto)\nHi");
        self::assertGreaterThanOrEqual(0, $inspector->clearCache());
        self::assertSame('8', $inspector->getWorld()->__debugInfo()['cacheSize']);
    }

    public function testSuccessfulDocumentCanExport(): void
    {
        $result = (new Inspector(new World()))->inspectString("#set page(height: auto)\nExport me");
        self::assertTrue($result->success());
        $doc = $result->getDocument();
        self::assertNotNull($doc);
        self::assertStringStartsWith('%PDF', $doc->toPdf()->bytes());
    }
}
