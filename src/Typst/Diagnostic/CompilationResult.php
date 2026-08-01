<?php

declare(strict_types=1);

namespace Typst\Diagnostic;

use FFI;
use FFI\CData;
use Typst\Document;
use Typst\Native;

final class CompilationResult
{
    private CData $handle;
    private bool $success;
    private ?Document $document = null;

    /** @var list<Diagnostic> */
    private array $diagnostics;

    private function __construct()
    {
    }

    /** @internal */
    public static function fromHandle(CData $handle): self
    {
        $self = new self();
        $self->handle = $handle;
        $self->success = ((int) Native::lib()->typst_compilation_result_success($handle)) === 1;

        $json = Native::bufferToString(Native::lib()->typst_compilation_result_diagnostics_json($handle));
        $self->diagnostics = self::parseDiagnostics($json !== '' ? $json : '[]');

        if ($self->success) {
            $docPtr = Native::lib()->typst_compilation_result_take_document($handle);
            if ($docPtr !== null && !FFI::isNull($docPtr)) {
                $self->document = new Document($docPtr);
            }
        }

        return $self;
    }

    public function __destruct()
    {
        if (!FFI::isNull($this->handle)) {
            Native::lib()->typst_compilation_result_free($this->handle);
        }
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function success(): bool
    {
        return $this->success;
    }

    /**
     * @return list<Diagnostic>
     */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    /**
     * @return list<Diagnostic>
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (Diagnostic $d): bool => $d->severity() === Severity::Warning,
        ));
    }

    /**
     * @return list<Diagnostic>
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (Diagnostic $d): bool => $d->severity() === Severity::Error,
        ));
    }

    public function hasWarnings(): bool
    {
        return $this->warnings() !== [];
    }

    public function hasErrors(): bool
    {
        return $this->errors() !== [];
    }

    /**
     * @return list<Diagnostic>
     */
    private static function parseDiagnostics(string $json): array
    {
        /** @var list<array{severity: int, message: string, span?: array{file: string, line: int, column: int, text: string}|null, hints?: list<string>}> $raw */
        $raw = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $out = [];
        foreach ($raw as $item) {
            $span = null;
            $spanData = $item['span'] ?? null;
            if (is_array($spanData)) {
                $span = new SourceSpan(
                    (string) $spanData['file'],
                    (int) $spanData['line'],
                    (int) $spanData['column'],
                    (string) $spanData['text'],
                );
            }
            $hints = $item['hints'] ?? [];
            $out[] = new Diagnostic(
                Severity::from((int) $item['severity']),
                (string) $item['message'],
                $span,
                array_map('strval', $hints),
            );
        }

        return $out;
    }
}
