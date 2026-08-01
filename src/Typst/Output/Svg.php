<?php

declare(strict_types=1);

namespace Typst\Output;

final class Svg implements OutputInterface
{
    use OutputTrait;

    public function __construct(string $data)
    {
        $this->data = $data;
    }
}
