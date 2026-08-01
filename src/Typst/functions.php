<?php

declare(strict_types=1);

namespace Typst;

/**
 * Returns the php-typst package / native library version string.
 */
function version(): string
{
    $version = Native::readCString(Native::lib()->typst_version()) ?? '0.0.0';

    return $version !== '' ? $version : '0.0.0';
}

/**
 * Returns the embedded Typst engine version string.
 */
function typst_version(): string
{
    $version = Native::readCString(Native::lib()->typst_engine_version()) ?? 'unknown';

    return $version !== '' ? $version : 'unknown';
}
