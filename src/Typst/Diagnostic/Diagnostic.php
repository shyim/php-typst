<?php

declare(strict_types=1);

namespace Typst\Diagnostic;

final class Diagnostic implements \Stringable
{
    /**
     * @param list<string> $hints
     */
    public function __construct(
        private readonly Severity $severity,
        private readonly string $message,
        private readonly ?SourceSpan $span,
        private readonly array $hints,
    ) {
    }

    public function severity(): Severity
    {
        return $this->severity;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function span(): ?SourceSpan
    {
        return $this->span;
    }

    /**
     * @return list<string>
     */
    public function hints(): array
    {
        return $this->hints;
    }

    public function __toString(): string
    {
        $severity = $this->severity === Severity::Error ? 'error' : 'warning';
        $location = '';
        if ($this->span !== null) {
            $location = sprintf(
                ' (at %s:%d:%d)',
                $this->span->file(),
                $this->span->line(),
                $this->span->column(),
            );
        }

        return "{$severity}: {$this->message}{$location}";
    }
}
