<?php

declare(strict_types=1);

namespace Typst\Tests;

use InvalidArgumentException as RootInvalidArgumentException;
use LogicException as RootLogicException;
use OutOfBoundsException as RootOutOfBoundsException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException as RootRuntimeException;
use Throwable;
use Typst\Compiler;
use Typst\Exception\ExceptionInterface;
use Typst\Exception\InvalidArgumentException;
use Typst\Exception\LogicException;
use Typst\Exception\OutOfBoundsException;
use Typst\Exception\RuntimeException;
use Typst\World;

final class ExceptionTest extends TestCase
{
    public function testExceptionInterfaceIsInterface(): void
    {
        self::assertTrue((new ReflectionClass(ExceptionInterface::class))->isInterface());
        self::assertTrue((new ReflectionClass(ExceptionInterface::class))->implementsInterface(Throwable::class));
    }

    public function testRuntimeExceptionHierarchy(): void
    {
        $r = new ReflectionClass(RuntimeException::class);
        self::assertTrue($r->isSubclassOf(RootRuntimeException::class));
        self::assertTrue($r->implementsInterface(ExceptionInterface::class));
        self::assertSame(1, RuntimeException::COMPILATION_FAILED);
        self::assertSame(2, RuntimeException::FILE_NOT_FOUND);
        self::assertSame(3, RuntimeException::WRITE_FAILED);
        self::assertSame(4, RuntimeException::FONT_INVALID);
        self::assertSame(5, RuntimeException::ENCODING_FAILED);
    }

    public function testLogicExceptionHierarchy(): void
    {
        $r = new ReflectionClass(LogicException::class);
        self::assertTrue($r->isSubclassOf(RootLogicException::class));
        self::assertTrue($r->implementsInterface(ExceptionInterface::class));
    }

    public function testInvalidArgumentExceptionHierarchy(): void
    {
        $r = new ReflectionClass(InvalidArgumentException::class);
        self::assertTrue($r->isSubclassOf(RootInvalidArgumentException::class));
        self::assertTrue($r->implementsInterface(ExceptionInterface::class));
    }

    public function testOutOfBoundsExceptionHierarchy(): void
    {
        $r = new ReflectionClass(OutOfBoundsException::class);
        self::assertTrue($r->isSubclassOf(RootOutOfBoundsException::class));
        self::assertTrue($r->implementsInterface(ExceptionInterface::class));
    }

    public function testCompilationFailureCatchableAsExceptionInterface(): void
    {
        $world = new World();
        $compiler = new Compiler($world);
        try {
            $compiler->compile($world->loadString('#bad()'));
            self::fail('Expected exception');
        } catch (ExceptionInterface $e) {
            self::assertInstanceOf(RuntimeException::class, $e);
            self::assertSame(RuntimeException::COMPILATION_FAILED, $e->getCode());
            self::assertNotSame('', $e->getMessage());
        }
    }

    public function testFileNotFoundCode(): void
    {
        try {
            (new World())->loadFile('/definitely/missing.typ');
            self::fail('Expected exception');
        } catch (RuntimeException $e) {
            self::assertSame(RuntimeException::FILE_NOT_FOUND, $e->getCode());
        }
    }

    public function testOutOfBoundsOnDocument(): void
    {
        $doc = (new Compiler(new World()))->compileString("#set page(height: auto)\nHi");
        $this->expectException(OutOfBoundsException::class);
        $doc->toImage(10);
    }
}
