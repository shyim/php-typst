<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typst\Compiler;
use Typst\World;

/**
 * Smoke tests for common Typst markup features (export produces non-empty SVG).
 */
final class MarkupTest extends TestCase
{
    private static Compiler $compiler;

    public static function setUpBeforeClass(): void
    {
        self::$compiler = new Compiler(new World());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function markupProvider(): iterable
    {
        yield 'bold' => ['This is *bold* text'];
        yield 'italic' => ['This is _italic_ text'];
        yield 'headings' => ["= Level 1\n== Level 2\n=== Level 3"];
        yield 'bullets' => ["- Apple\n- Banana\n- Cherry"];
        yield 'numbered' => ["+ First\n+ Second\n+ Third"];
        yield 'raw block' => ["```rust\nfn main() {}\n```"];
        yield 'inline code' => ['Use `println!` to print'];
        yield 'strike' => ['#strike[removed]'];
        yield 'sub super' => ['H#sub[2]O and x#super[2]'];
        yield 'math' => ['$ x = (-b +- sqrt(b^2 - 4a c)) / (2a) $'];
        yield 'table' => ['#table(columns: 2, [A], [B], [1], [2])'];
        yield 'link' => ['#link("https://typst.app")[Typst]'];
    }

    #[DataProvider('markupProvider')]
    public function testMarkupCompilesToSvg(string $body): void
    {
        $doc = self::$compiler->compileString("#set page(height: auto)\n{$body}");
        self::assertSame(1, $doc->pageCount());
        self::assertGreaterThan(0, $doc->toSvg()->size());
    }
}
