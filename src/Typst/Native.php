<?php

declare(strict_types=1);

namespace Typst;

use FFI;
use FFI\CData;
use Shyim\BinaryDownloader\Binaries;
use Shyim\BinaryDownloader\Exception\LibraryUnavailableExceptionInterface;
use Typst\Exception\InvalidArgumentException;
use Typst\Exception\LogicException;
use Typst\Exception\OutOfBoundsException;
use Typst\Exception\RuntimeException;

/**
 * Low-level bridge to the Rust-built libtypst_ffi shared library.
 *
 * The library is installed by shyim/composer-binary-downloader into
 * `bin/<target>/` on composer install (or on first use via {@see ensureLibrary()}).
 *
 * @internal
 */
final class Native
{
    private static ?FFI $ffi = null;

    public static function lib(): FFI
    {
        return self::$ffi ??= self::load();
    }

    public static function load(?string $libraryPath = null): FFI
    {
        $header = self::headerPath();
        $lib = $libraryPath ?? self::discoverLibraryPath();

        if (!is_file($lib)) {
            throw new RuntimeException(
                "Typst native library not found at '{$lib}'. "
                . 'Run `composer binary:install typst` or set TYPST_LIBRARY.',
                RuntimeException::COMPILATION_FAILED,
            );
        }

        $cdef = file_get_contents($header);
        if ($cdef === false) {
            throw new RuntimeException(
                "Failed to read FFI definitions at '{$header}'",
                RuntimeException::COMPILATION_FAILED,
            );
        }

        self::$ffi = FFI::cdef($cdef, $lib);

        return self::$ffi;
    }

    public static function reset(): void
    {
        self::$ffi = null;
    }

    /**
     * Absolute path to the installed (or overridden) shared library.
     *
     * Prefers a path already on disk; downloads when missing and allowed.
     */
    public static function discoverLibraryPath(): string
    {
        try {
            return Binaries::path('typst');
        } catch (LibraryUnavailableExceptionInterface) {
            // Not installed yet / plugins skipped — try on-demand install.
            return Binaries::install('typst');
        }
    }

    public static function headerPath(): string
    {
        return dirname(__DIR__, 2) . '/resources/typst_ffi.cdef';
    }

    public static function throwLastError(): never
    {
        $ffi = self::lib();
        $kind = (int) $ffi->typst_last_error_kind();
        $code = (int) $ffi->typst_last_error_code();
        $msgPtr = $ffi->typst_last_error_message();
        $message = self::readCString($msgPtr) ?? 'Unknown Typst FFI error';

        match ($kind) {
            1 => throw new InvalidArgumentException($message),
            3 => throw new LogicException($message),
            4 => throw new OutOfBoundsException($message),
            default => throw new RuntimeException($message, $code !== 0 ? $code : RuntimeException::COMPILATION_FAILED),
        };
    }

    public static function requirePtr(?CData $ptr, string $context = 'operation'): CData
    {
        if ($ptr === null || FFI::isNull($ptr)) {
            self::throwLastError();
        }

        return $ptr;
    }

    public static function checkOk(int $status): void
    {
        if ($status !== 0) {
            self::throwLastError();
        }
    }

    /**
     * Read a C string pointer (or already-converted PHP string from FFI).
     */
    public static function readCString(mixed $ptr): ?string
    {
        if ($ptr === null) {
            return null;
        }
        if (is_string($ptr)) {
            return $ptr;
        }
        if ($ptr instanceof CData) {
            if (FFI::isNull($ptr)) {
                return null;
            }

            return FFI::string($ptr);
        }

        return null;
    }

    public static function bufferToString(CData $buf): string
    {
        /** @var int $rawLen */
        $rawLen = $buf->len;
        $len = max(0, $rawLen);
        /** @var CData|null $data */
        $data = $buf->data;
        if ($len === 0 || $data === null || FFI::isNull($data)) {
            self::lib()->typst_buffer_free($buf);

            return '';
        }

        $bytes = FFI::string($data, $len);
        self::lib()->typst_buffer_free($buf);

        return $bytes;
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public static function encodeInputs(?array $inputs): ?string
    {
        if ($inputs === null) {
            return null;
        }

        self::assertInputsEncodable($inputs, '');

        try {
            // Top-level inputs must be a JSON object. PHP encodes [] as an array.
            $payload = $inputs === [] ? new \stdClass() : $inputs;

            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('Failed to encode inputs as JSON: ' . $e->getMessage());
        }
    }

    /**
     * Reject objects/resources (ext-typst parity). Scalars, null, and arrays only.
     *
     * @param array<mixed> $inputs
     */
    private static function assertInputsEncodable(array $inputs, string $path): void
    {
        foreach ($inputs as $key => $value) {
            $child = $path === '' ? (string) $key : $path . '.' . $key;
            if (is_object($value)) {
                throw new InvalidArgumentException(
                    'Unsupported input value type: ' . $value::class . " at key '{$child}'",
                );
            }
            if (is_resource($value)) {
                throw new InvalidArgumentException(
                    "Unsupported input value type: resource at key '{$child}'",
                );
            }
            if (is_array($value)) {
                self::assertInputsEncodable($value, $child);
            }
        }
    }

    /**
     * @return array{0: ?CData, 1: int}
     */
    public static function stringToU8(string $s): array
    {
        $len = strlen($s);
        if ($len === 0) {
            return [null, 0];
        }

        $buf = self::lib()->new('uint8_t[' . $len . ']', false);
        FFI::memcpy($buf, $s, $len);

        return [$buf, $len];
    }
}
