<?php

declare(strict_types=1);

namespace Typst\Tests;

use PHPUnit\Framework\TestCase;
use Typst\Exception\InvalidArgumentException;
use Typst\ImageFormat;
use Typst\ImageOptions;
use Typst\PdfOptions;
use Typst\PdfValidator;
use Typst\PdfVersion;

use const INF;
use const NAN;

final class OptionsTest extends TestCase
{
    public function testImageOptionsDefault(): void
    {
        $opts = new ImageOptions();
        self::assertSame(ImageFormat::Png, $opts->format);
        self::assertSame(85, $opts->quality);
        self::assertSame(144.0, $opts->dpi);
    }

    public function testImageOptionsAllParameters(): void
    {
        $opts = new ImageOptions(ImageFormat::Jpeg, 90, 300.0);
        self::assertSame(ImageFormat::Jpeg, $opts->format);
        self::assertSame(90, $opts->quality);
        self::assertSame(300.0, $opts->dpi);
    }

    public function testImageOptionsQualityBounds(): void
    {
        self::assertSame(1, (new ImageOptions(null, 1))->quality);
        self::assertSame(100, (new ImageOptions(null, 100))->quality);
    }

    public function testImageOptionsQualityZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageOptions(null, 0);
    }

    public function testImageOptionsQuality101Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageOptions(null, 101);
    }

    public function testImageOptionsDpiZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageOptions(null, null, 0.0);
    }

    public function testImageOptionsNanDpiThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageOptions(null, null, NAN);
    }

    public function testImageOptionsInfDpiThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageOptions(null, null, INF);
    }

    public function testImageOptionsWithersAreImmutable(): void
    {
        $opts = new ImageOptions();
        $new = $opts
            ->withFormat(ImageFormat::Jpeg)
            ->withQuality(50)
            ->withDpi(72.0);

        self::assertSame(ImageFormat::Png, $opts->format);
        self::assertSame(85, $opts->quality);
        self::assertSame(144.0, $opts->dpi);

        self::assertSame(ImageFormat::Jpeg, $new->format);
        self::assertSame(50, $new->quality);
        self::assertSame(72.0, $new->dpi);
    }

    public function testImageOptionsWithQualityInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ImageOptions())->withQuality(0);
    }

    public function testImageOptionsWithDpiInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ImageOptions())->withDpi(-1.0);
    }

    public function testImageOptionsPropertiesReadOnly(): void
    {
        $opts = new ImageOptions();
        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line */
        $opts->quality = 50;
    }

    public function testPdfOptionsDefault(): void
    {
        $opts = new PdfOptions();
        self::assertNull($opts->identifier);
        self::assertNull($opts->timestamp);
        self::assertNull($opts->firstPage);
        self::assertNull($opts->lastPage);
        self::assertNull($opts->version);
        self::assertNull($opts->validator);
        self::assertTrue($opts->tagged);
    }

    public function testPdfOptionsAllParameters(): void
    {
        $opts = new PdfOptions(
            identifier: 'my-doc',
            timestamp: 1_700_000_000,
            first_page: 0,
            last_page: 5,
            version: PdfVersion::V1_7,
            validator: PdfValidator::A2b,
            tagged: false,
        );
        self::assertSame('my-doc', $opts->identifier);
        self::assertSame(1_700_000_000, $opts->timestamp);
        self::assertSame(0, $opts->firstPage);
        self::assertSame(5, $opts->lastPage);
        self::assertSame(PdfVersion::V1_7, $opts->version);
        self::assertSame(PdfValidator::A2b, $opts->validator);
        self::assertFalse($opts->tagged);
    }

    public function testPdfOptionsNegativeFirstPageThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PdfOptions(first_page: -1);
    }

    public function testPdfOptionsNegativeLastPageThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PdfOptions(last_page: -1);
    }

    public function testPdfOptionsFirstGreaterThanLastThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PdfOptions(first_page: 3, last_page: 1);
    }

    public function testPdfOptionsWithersAreImmutable(): void
    {
        $opts = new PdfOptions();
        $new = $opts
            ->withIdentifier('id')
            ->withTimestamp(100)
            ->withFirstPage(0)
            ->withLastPage(2)
            ->withVersion(PdfVersion::V1_4)
            ->withTagged(false);

        self::assertNull($opts->identifier);
        self::assertTrue($opts->tagged);
        self::assertSame('id', $new->identifier);
        self::assertSame(100, $new->timestamp);
        self::assertSame(0, $new->firstPage);
        self::assertSame(2, $new->lastPage);
        self::assertSame(PdfVersion::V1_4, $new->version);
        self::assertFalse($new->tagged);
    }

    public function testPdfOptionsWithFirstPageInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PdfOptions(last_page: 1))->withFirstPage(5);
    }

    public function testEnumValues(): void
    {
        self::assertSame('png', ImageFormat::Png->value);
        self::assertSame('jpeg', ImageFormat::Jpeg->value);
        self::assertSame('1.7', PdfVersion::V1_7->value);
        self::assertSame('2.0', PdfVersion::V2_0->value);
        self::assertSame('a-2b', PdfValidator::A2b->value);
        self::assertSame('ua-1', PdfValidator::Ua1->value);
    }
}
