<?php

declare(strict_types=1);

namespace Typst\Output;

use Typst\Exception\InvalidArgumentException;
use Typst\Exception\OutOfBoundsException;
use Typst\Exception\RuntimeException;

trait OutputTrait
{
    private string $data;

    public function bytes(?int $offset = null, ?int $limit = null): string
    {
        $offset ??= 0;
        if ($offset < 0) {
            throw new InvalidArgumentException("Offset must be non-negative, got {$offset}.");
        }
        if ($offset > strlen($this->data)) {
            throw new OutOfBoundsException(
                "Offset {$offset} is beyond data size " . strlen($this->data) . '.',
            );
        }
        if ($limit === null) {
            return substr($this->data, $offset);
        }
        if ($limit < 0) {
            throw new InvalidArgumentException("Limit must be non-negative, got {$limit}.");
        }

        return substr($this->data, $offset, $limit);
    }

    public function size(): int
    {
        return strlen($this->data);
    }

    public function save(string $path): void
    {
        $ok = @file_put_contents($path, $this->data);
        if ($ok === false) {
            throw new RuntimeException(
                "Failed to write output to '{$path}'",
                RuntimeException::WRITE_FAILED,
            );
        }
    }

    public function __toString(): string
    {
        return $this->data;
    }
}
