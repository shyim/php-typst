<?php

declare(strict_types=1);

namespace Typst;

use Typst\Exception\InvalidArgumentException;

final readonly class ImageOptions
{
    public ImageFormat $format;

    /** @var int<1, 100> */
    public int $quality;

    public float $dpi;

    public function __construct(?ImageFormat $format = null, ?int $quality = null, ?float $dpi = null)
    {
        $quality ??= 85;
        if ($quality < 1 || $quality > 100) {
            throw new InvalidArgumentException("Quality must be between 1 and 100, got {$quality}");
        }

        $dpi ??= 144.0;
        if (!is_finite($dpi) || $dpi <= 0.0) {
            throw new InvalidArgumentException('DPI must be a finite positive number');
        }

        $this->format = $format ?? ImageFormat::Png;
        $this->quality = $quality;
        $this->dpi = $dpi;
    }

    public function withFormat(ImageFormat $format): self
    {
        return new self($format, $this->quality, $this->dpi);
    }

    public function withQuality(int $quality): self
    {
        return new self($this->format, $quality, $this->dpi);
    }

    public function withDpi(float $dpi): self
    {
        return new self($this->format, $this->quality, $dpi);
    }

    /** @internal */
    public function formatInt(): int
    {
        return $this->format === ImageFormat::Jpeg ? 1 : 0;
    }
}
