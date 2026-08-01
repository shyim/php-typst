<?php

declare(strict_types=1);

namespace Typst;

use FFI;
use FFI\CData;
use Typst\Exception\LogicException;
use Typst\Output\Image;
use Typst\Output\Pdf;
use Typst\Output\Svg;

final class Document
{
    /** @internal */
    public function __construct(
        private CData $handle,
    ) {
    }

    public function __destruct()
    {
        if (!FFI::isNull($this->handle)) {
            Native::lib()->typst_document_free($this->handle);
        }
    }

    public function __clone(): void
    {
        throw new LogicException('Document cannot be cloned');
    }

    /** @internal */
    public function handle(): CData
    {
        return $this->handle;
    }

    public function pageCount(): int
    {
        return (int) Native::lib()->typst_document_page_count($this->handle);
    }

    public function pageWidth(?int $page = null): float
    {
        $w = (float) Native::lib()->typst_document_page_width($this->handle, $page ?? 0);
        if (is_nan($w)) {
            Native::throwLastError();
        }

        return $w;
    }

    public function pageHeight(?int $page = null): float
    {
        $h = (float) Native::lib()->typst_document_page_height($this->handle, $page ?? 0);
        if (is_nan($h)) {
            Native::throwLastError();
        }

        return $h;
    }

    public function toPdf(?PdfOptions $options = null): Pdf
    {
        $options ??= new PdfOptions();
        $ffi = Native::lib();

        $result = $ffi->typst_document_to_pdf(
            $this->handle,
            $options->identifier,
            $options->timestamp ?? PHP_INT_MIN,
            $options->firstPage ?? -1,
            $options->lastPage ?? -1,
            $options->version?->value,
            $options->validator?->value,
            $options->tagged ? 1 : 0,
        );

        $bytes = Native::bufferToString($result->data);
        if ($bytes === '' && (int) $result->page_count === 0 && (int) $ffi->typst_last_error_kind() !== 0) {
            Native::throwLastError();
        }

        return new Pdf($bytes, (int) $result->page_count);
    }

    public function toImage(?int $page = null, ?ImageOptions $options = null): Image
    {
        $options ??= new ImageOptions();
        $result = Native::lib()->typst_document_to_image(
            $this->handle,
            $page ?? 0,
            $options->formatInt(),
            $options->quality,
            $options->dpi,
        );

        $bytes = Native::bufferToString($result->data);
        if ($bytes === '' && (int) $result->width === 0 && (int) Native::lib()->typst_last_error_kind() !== 0) {
            Native::throwLastError();
        }

        $format = ((int) $result->format) === 1 ? ImageFormat::Jpeg : ImageFormat::Png;

        return new Image($bytes, $format, (int) $result->width, (int) $result->height);
    }

    /**
     * @return list<Image>
     */
    public function toImages(?ImageOptions $options = null): array
    {
        $options ??= new ImageOptions();
        $count = (int) Native::lib()->typst_document_to_images_count($this->handle);
        $images = [];
        for ($i = 0; $i < $count; $i++) {
            $images[] = $this->toImage($i, $options);
        }

        return $images;
    }

    public function toSvg(?int $page = null): Svg
    {
        $buf = Native::lib()->typst_document_to_svg($this->handle, $page ?? 0);
        $bytes = Native::bufferToString($buf);
        if ($bytes === '' && (int) Native::lib()->typst_last_error_kind() !== 0) {
            Native::throwLastError();
        }

        return new Svg($bytes);
    }

    /**
     * @return list<Svg>
     */
    public function toSvgs(): array
    {
        $ffi = Native::lib();
        $count = $ffi->new('size_t');
        $ptrs = $ffi->typst_document_to_svg_all($this->handle, FFI::addr($count));
        if ($ptrs === null || FFI::isNull($ptrs)) {
            Native::throwLastError();
        }

        $n = (int) $count->cdata;
        $svgs = [];
        for ($i = 0; $i < $n; $i++) {
            $buf = $ptrs[$i];
            $len = (int) $buf->len;
            $bytes = $len > 0 && $buf->data !== null && !FFI::isNull($buf->data)
                ? FFI::string($buf->data, $len)
                : '';
            $svgs[] = new Svg($bytes);
        }
        $ffi->typst_buffers_free($ptrs, $n);

        return $svgs;
    }
}
