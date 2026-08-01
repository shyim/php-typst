<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Typst\Native;

final class NativeTest extends TestCase
{
    public function testLibraryPathExists(): void
    {
        $path = Native::discoverLibraryPath();
        self::assertFileExists($path);
        self::assertNotSame(0, filesize($path));
    }

    public function testHeaderPathExists(): void
    {
        self::assertFileExists(Native::headerPath());
        self::assertStringContainsString('typst_world_new', (string) file_get_contents(Native::headerPath()));
    }

    public function testLibLoads(): void
    {
        $ffi = Native::lib();
        self::assertNotNull($ffi->typst_version());
    }

    public function testEncodeInputsNull(): void
    {
        self::assertNull(Native::encodeInputs(null));
    }

    public function testEncodeInputsEmptyObject(): void
    {
        self::assertSame('{}', Native::encodeInputs([]));
    }

    public function testEncodeInputsScalars(): void
    {
        $json = Native::encodeInputs([
            's' => 'hello',
            'i' => 42,
            'f' => 1.5,
            'b' => true,
            'n' => null,
        ]);
        self::assertNotNull($json);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('hello', $decoded['s']);
        self::assertSame(42, $decoded['i']);
        self::assertTrue($decoded['b']);
        self::assertNull($decoded['n']);
    }

    public function testEncodeInputsRejectsObject(): void
    {
        $this->expectException(\Typst\Exception\InvalidArgumentException::class);
        Native::encodeInputs(['obj' => new \stdClass()]);
    }

    public function testEncodeInputsRejectsResource(): void
    {
        $h = fopen('php://memory', 'r');
        self::assertNotFalse($h);
        try {
            $this->expectException(\Typst\Exception\InvalidArgumentException::class);
            Native::encodeInputs(['r' => $h]);
        } finally {
            fclose($h);
        }
    }
}
