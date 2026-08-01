<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Typst\Compiler;
use Typst\Document;
use Typst\Exception\InvalidArgumentException;
use Typst\Exception\OutOfBoundsException;
use Typst\ImageFormat;
use Typst\ImageOptions;
use Typst\Output\Image;
use Typst\Output\Pdf;
use Typst\Output\Svg;
use Typst\PdfOptions;
use Typst\World;

final class DocumentTest extends TestCase
{
    private static World $world;
    private static Compiler $compiler;

    public static function setUpBeforeClass(): void
    {
        self::$world = new World();
        self::$compiler = new Compiler(self::$world);
    }

    private function compileSimple(string $body = 'Hello'): Document
    {
        return self::$compiler->compileString("#set page(height: auto)\n{$body}");
    }

    public function testPageCountSinglePage(): void
    {
        self::assertSame(1, $this->compileSimple()->pageCount());
    }

    public function testPageCountMultiPage(): void
    {
        $doc = self::$compiler->compileString("Page 1\n#pagebreak()\nPage 2\n#pagebreak()\nPage 3");
        self::assertSame(3, $doc->pageCount());
    }

    public function testPageWidthAndHeightPositive(): void
    {
        $doc = $this->compileSimple();
        self::assertGreaterThan(0.0, $doc->pageWidth());
        self::assertGreaterThan(0.0, $doc->pageHeight());
    }

    public function testPageWidthOutOfRangeThrows(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->compileSimple()->pageWidth(99);
    }

    public function testPageWidthNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->compileSimple()->pageWidth(-1);
    }

    public function testToPdfReturnsPdf(): void
    {
        $pdf = $this->compileSimple()->toPdf();
        self::assertInstanceOf(Pdf::class, $pdf);
        self::assertStringStartsWith('%PDF', $pdf->bytes());
        self::assertGreaterThan(0, $pdf->size());
    }

    public function testToPdfPageCount(): void
    {
        $doc = self::$compiler->compileString("Page 1\n#pagebreak()\nPage 2");
        self::assertSame(2, $doc->toPdf()->pageCount());
    }

    public function testToPdfWithPageRange(): void
    {
        $doc = self::$compiler->compileString("P1\n#pagebreak()\nP2\n#pagebreak()\nP3");
        // Tagged PDF + partial page ranges can fail in typst-pdf; disable tagging.
        $pdf = $doc->toPdf(new PdfOptions(first_page: 0, last_page: 0, tagged: false));
        self::assertSame(1, $pdf->pageCount());
    }

    public function testToImageDefaultsPng(): void
    {
        $img = $this->compileSimple()->toImage();
        self::assertInstanceOf(Image::class, $img);
        self::assertSame(ImageFormat::Png, $img->format());
        self::assertStringStartsWith("\x89PNG", $img->bytes());
        self::assertGreaterThan(0, $img->width());
        self::assertGreaterThan(0, $img->height());
    }

    public function testToImageJpeg(): void
    {
        $img = $this->compileSimple()->toImage(null, new ImageOptions(ImageFormat::Jpeg, 80, 72.0));
        self::assertSame(ImageFormat::Jpeg, $img->format());
        self::assertSame("\xFF\xD8", substr($img->bytes(), 0, 2));
    }

    public function testToImageHigherDpiLargerImage(): void
    {
        $doc = $this->compileSimple();
        $low = $doc->toImage(null, new ImageOptions(null, null, 72.0));
        $high = $doc->toImage(null, new ImageOptions(null, null, 300.0));
        self::assertGreaterThan($low->width(), $high->width());
        self::assertGreaterThan($low->height(), $high->height());
    }

    public function testToImageSpecificPage(): void
    {
        $doc = self::$compiler->compileString("Page 1\n#pagebreak()\nPage 2");
        self::assertInstanceOf(Image::class, $doc->toImage(0));
        self::assertInstanceOf(Image::class, $doc->toImage(1));
    }

    public function testToImagePageOutOfRangeThrows(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->compileSimple()->toImage(999);
    }

    public function testToImageNegativePageThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->compileSimple()->toImage(-1);
    }

    public function testToImagesReturnsAllPages(): void
    {
        $doc = self::$compiler->compileString("Page 1\n#pagebreak()\nPage 2\n#pagebreak()\nPage 3");
        $images = $doc->toImages();
        self::assertCount(3, $images);
        foreach ($images as $img) {
            self::assertInstanceOf(Image::class, $img);
        }
    }

    public function testToSvgReturnsSvg(): void
    {
        $svg = $this->compileSimple()->toSvg();
        self::assertInstanceOf(Svg::class, $svg);
        self::assertStringContainsString('<svg', $svg->bytes());
        self::assertGreaterThan(0, $svg->size());
    }

    public function testToSvgsReturnsAllPages(): void
    {
        $doc = self::$compiler->compileString("A\n#pagebreak()\nB");
        $svgs = $doc->toSvgs();
        self::assertCount(2, $svgs);
        foreach ($svgs as $svg) {
            self::assertStringContainsString('<svg', $svg->bytes());
        }
    }

    public function testToSvgPageOutOfRangeThrows(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->compileSimple()->toSvg(5);
    }

    public function testExportMultipleTimesWithoutRecompile(): void
    {
        $doc = $this->compileSimple('Stable');
        $pdf1 = $doc->toPdf()->bytes();
        $pdf2 = $doc->toPdf()->bytes();
        self::assertSame($pdf1, $pdf2);
        self::assertStringStartsWith("\x89PNG", $doc->toImage()->bytes());
        self::assertStringContainsString('<svg', $doc->toSvg()->bytes());
    }
}
