<?php

declare(strict_types=1);

namespace Typst\Output;

final class Pdf implements OutputInterface
{
    use OutputTrait;

    public function __construct(
        string $data,
        private readonly int $pageCount,
    ) {
        $this->data = $data;
    }

    public function pageCount(): int
    {
        return $this->pageCount;
    }
}
