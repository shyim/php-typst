<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Stringable;
use Typst\Compiler;
use Typst\Exception\InvalidArgumentException;
use Typst\Exception\OutOfBoundsException;
use Typst\Exception\RuntimeException;
use Typst\ImageFormat;
use Typst\ImageOptions;
use Typst\Output\Image;
use Typst\Output\OutputInterface;
use Typst\Output\Pdf;
use Typst\Output\Svg;
use Typst\World;

final class OutputTest extends TestCase
{
    private static Compiler $compiler;

    public static function setUpBeforeClass(): void
    {
        self::$compiler = new Compiler(new World());
    }

    private function pdf(): Pdf
    {
        return self::$compiler->compileString("#set page(height: auto)\nHello")->toPdf();
    }

    private function image(): Image
    {
        return self::$compiler->compileString("#set page(height: auto)\nHello")->toImage();
    }

    private function svg(): Svg
    {
        return self::$compiler->compileString("#set page(height: auto)\nHello")->toSvg();
    }

    public function testPdfBytesAndSize(): void
    {
        $pdf = $this->pdf();
        self::assertStringStartsWith('%PDF', $pdf->bytes());
        self::assertSame(strlen($pdf->bytes()), $pdf->size());
        self::assertSame($pdf->bytes(), (string) $pdf);
    }

    public function testPdfChunkedBytes(): void
    {
        $pdf = $this->pdf();
        $all = $pdf->bytes();
        self::assertSame(substr($all, 0, 4), $pdf->bytes(0, 4));
        self::assertSame(substr($all, 4, 8), $pdf->bytes(4, 8));
        self::assertSame(substr($all, 10), $pdf->bytes(10));
    }

    public function testPdfBytesNegativeOffsetThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pdf()->bytes(-1);
    }

    public function testPdfBytesOffsetBeyondSizeThrows(): void
    {
        $pdf = $this->pdf();
        $this->expectException(OutOfBoundsException::class);
        $pdf->bytes($pdf->size() + 10);
    }

    public function testPdfBytesNegativeLimitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pdf()->bytes(0, -1);
    }

    public function testPdfSave(): void
    {
        $pdf = $this->pdf();
        $tmp = tempnam(sys_get_temp_dir(), 'typst_pdf_') . '.pdf';
        try {
            $pdf->save($tmp);
            self::assertFileExists($tmp);
            self::assertSame($pdf->bytes(), file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function testPdfSaveInvalidPathThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->pdf()->save('/nonexistent/dir/file.pdf');
    }

    public function testPdfImplementsInterfaces(): void
    {
        $pdf = $this->pdf();
        self::assertInstanceOf(OutputInterface::class, $pdf);
        self::assertInstanceOf(Stringable::class, $pdf);
    }

    public function testImagePngSignatureAndDimensions(): void
    {
        $img = $this->image();
        self::assertStringStartsWith("\x89PNG", $img->bytes());
        self::assertSame(ImageFormat::Png, $img->format());
        self::assertGreaterThan(0, $img->width());
        self::assertGreaterThan(0, $img->height());
        self::assertSame(strlen($img->bytes()), $img->size());
        self::assertSame($img->bytes(), (string) $img);
    }

    public function testImageJpegFormat(): void
    {
        $img = self::$compiler
            ->compileString("#set page(height: auto)\nJpeg")
            ->toImage(null, new ImageOptions(ImageFormat::Jpeg));
        self::assertSame(ImageFormat::Jpeg, $img->format());
        self::assertSame("\xFF\xD8", substr($img->bytes(), 0, 2));
    }

    public function testImageSave(): void
    {
        $img = $this->image();
        $tmp = tempnam(sys_get_temp_dir(), 'typst_img_') . '.png';
        try {
            $img->save($tmp);
            self::assertSame($img->bytes(), file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function testImageImplementsInterfaces(): void
    {
        $img = $this->image();
        self::assertInstanceOf(OutputInterface::class, $img);
        self::assertInstanceOf(Stringable::class, $img);
    }

    public function testSvgContentAndSave(): void
    {
        $svg = $this->svg();
        self::assertStringContainsString('<svg', $svg->bytes());
        self::assertSame(strlen($svg->bytes()), $svg->size());
        self::assertSame($svg->bytes(), (string) $svg);

        $tmp = tempnam(sys_get_temp_dir(), 'typst_svg_') . '.svg';
        try {
            $svg->save($tmp);
            self::assertSame($svg->bytes(), file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function testSvgImplementsInterfaces(): void
    {
        $svg = $this->svg();
        self::assertInstanceOf(OutputInterface::class, $svg);
        self::assertInstanceOf(Stringable::class, $svg);
    }
}
