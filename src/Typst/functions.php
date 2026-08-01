<?php

declare(strict_types=1);

namespace Typst;

/**
 * Returns the php-typst package / native library version string.
 *
 * @return non-empty-string
 */
function version(): string
{
    return Native::readCString(Native::lib()->typst_version()) ?? '0.0.0';
}

/**
 * Returns the embedded Typst engine version string.
 *
 * @return non-empty-string
 */
function typst_version(): string
{
    return Native::readCString(Native::lib()->typst_engine_version()) ?? 'unknown';
}
