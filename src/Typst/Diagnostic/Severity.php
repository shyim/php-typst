<?php

declare(strict_types=1);

namespace Typst\Diagnostic;

enum Severity: int
{
    case Error = 0;
    case Warning = 1;
}
