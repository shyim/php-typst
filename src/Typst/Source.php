<?php

declare(strict_types=1);

namespace Typst;

use FFI;
use FFI\CData;
use Typst\Exception\LogicException;

final class Source
{
    /** @internal */
    public function __construct(
        private CData $handle,
    ) {
    }

    public function __destruct()
    {
        if (!FFI::isNull($this->handle)) {
            Native::lib()->typst_source_free($this->handle);
        }
    }

    public function __clone(): void
    {
        throw new LogicException('Source cannot be cloned');
    }

    /** @internal */
    public function handle(): CData
    {
        return $this->handle;
    }

    public function getId(): int
    {
        return (int) Native::lib()->typst_source_get_id($this->handle);
    }

    public function getText(): string
    {
        $text = Native::bufferToString(Native::lib()->typst_source_get_text($this->handle));
        if ($text === '' && (int) Native::lib()->typst_last_error_kind() !== 0) {
            Native::throwLastError();
        }

        return $text;
    }
}
