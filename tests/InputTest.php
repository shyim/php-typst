<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use stdClass;
use Typst\Compiler;
use Typst\Exception\InvalidArgumentException;
use Typst\World;

final class InputTest extends TestCase
{
    private static World $world;
    private static Compiler $compiler;

    public static function setUpBeforeClass(): void
    {
        self::$world = new World();
        self::$compiler = new Compiler(self::$world);
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private function compileWith(array $inputs, string $body = '#sys.inputs.at("key")'): int
    {
        $source = self::$world->loadString("#set page(height: auto)\n{$body}");

        return self::$compiler->compile($source, $inputs)->pageCount();
    }

    public function testStringInput(): void
    {
        self::assertSame(1, $this->compileWith(['key' => 'hello']));
    }

    public function testIntInput(): void
    {
        self::assertSame(1, $this->compileWith(['key' => 42]));
    }

    public function testFloatInput(): void
    {
        self::assertSame(1, $this->compileWith(['key' => 3.14]));
    }

    public function testBoolInput(): void
    {
        self::assertSame(1, $this->compileWith(['key' => true]));
    }

    public function testNullInput(): void
    {
        self::assertSame(1, $this->compileWith(['key' => null]));
    }

    public function testArrayInput(): void
    {
        self::assertSame(1, $this->compileWith(['key' => ['a', 'b', 'c']]));
    }

    public function testNestedDictInput(): void
    {
        self::assertSame(1, $this->compileWith(['key' => ['inner' => 'value']]));
    }

    public function testMixedTypeInputs(): void
    {
        $source = self::$world->loadString(
            "#set page(height: auto)\n#sys.inputs.at(\"str\") #sys.inputs.at(\"num\")",
        );
        $doc = self::$compiler->compile($source, [
            'str' => 'hello',
            'num' => 42,
        ]);
        self::assertSame(1, $doc->pageCount());
    }

    public function testObjectInputThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->compileWith(['key' => new stdClass()]);
    }

    public function testResourceInputThrows(): void
    {
        $handle = fopen('php://memory', 'r');
        self::assertNotFalse($handle);
        try {
            $this->expectException(InvalidArgumentException::class);
            $this->compileWith(['key' => $handle]);
        } finally {
            fclose($handle);
        }
    }

    public function testNestedObjectInArrayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->compileWith(['key' => ['nested' => new stdClass()]]);
    }

    public function testEmptyInputs(): void
    {
        $source = self::$world->loadString("#set page(height: auto)\nHello");
        $doc1 = self::$compiler->compile($source, []);
        $doc2 = self::$compiler->compile($source, null);
        self::assertSame($doc1->pageCount(), $doc2->pageCount());
    }

    public function testInputsRenderedInDocument(): void
    {
        $source = self::$world->loadString("#set page(height: auto)\nName: #sys.inputs.at(\"name\")");
        $pdf = self::$compiler->compile($source, ['name' => 'Alice'])->toPdf();
        self::assertStringStartsWith('%PDF', $pdf->bytes());
    }
}
