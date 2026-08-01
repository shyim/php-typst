<?php

declare(strict_types=1);

namespace Typst\Output;

interface OutputInterface extends \Stringable
{
    public function bytes(?int $offset = null, ?int $limit = null): string;

    public function size(): int;

    public function save(string $path): void;
}
