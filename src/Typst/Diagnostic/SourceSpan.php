<?php

declare(strict_types=1);

namespace Typst\Diagnostic;

final class SourceSpan
{
    public function __construct(
        private readonly string $file,
        private readonly int $line,
        private readonly int $column,
        private readonly string $text,
    ) {
    }

    public function file(): string
    {
        return $this->file;
    }

    public function line(): int
    {
        return $this->line;
    }

    public function column(): int
    {
        return $this->column;
    }

    public function text(): string
    {
        return $this->text;
    }
}
