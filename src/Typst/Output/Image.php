<?php

declare(strict_types=1);

namespace Typst\Output;

use Typst\ImageFormat;

final class Image implements OutputInterface
{
    use OutputTrait;

    public function __construct(
        string $data,
        private readonly ImageFormat $format,
        private readonly int $width,
        private readonly int $height,
    ) {
        $this->data = $data;
    }

    public function format(): ImageFormat
    {
        return $this->format;
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }
}
