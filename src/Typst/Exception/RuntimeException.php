<?php

declare(strict_types=1);

namespace Typst\Exception;

final class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    public const COMPILATION_FAILED = 1;
    public const FILE_NOT_FOUND = 2;
    public const WRITE_FAILED = 3;
    public const FONT_INVALID = 4;
    public const ENCODING_FAILED = 5;
}
