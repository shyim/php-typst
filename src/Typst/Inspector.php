<?php

declare(strict_types=1);

namespace Typst;

use FFI;
use FFI\CData;
use Typst\Diagnostic\CompilationResult;

final class Inspector
{
    private CData $handle;

    public function __construct(World $world)
    {
        $ptr = Native::lib()->typst_inspector_new($world->handle());
        $this->handle = Native::requirePtr($ptr, 'Inspector construction');
    }

    public function __destruct()
    {
        if (!FFI::isNull($this->handle)) {
            Native::lib()->typst_inspector_free($this->handle);
        }
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public function inspect(Source $source, ?array $inputs = null): CompilationResult
    {
        $ptr = Native::lib()->typst_inspector_inspect(
            $this->handle,
            $source->handle(),
            Native::encodeInputs($inputs),
        );

        return CompilationResult::fromHandle(Native::requirePtr($ptr, 'inspect'));
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public function inspectString(string $source, ?array $inputs = null): CompilationResult
    {
        [$buf, $len] = Native::stringToU8($source);
        $ptr = Native::lib()->typst_inspector_inspect_string(
            $this->handle,
            $buf,
            $len,
            Native::encodeInputs($inputs),
        );

        return CompilationResult::fromHandle(Native::requirePtr($ptr, 'inspectString'));
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public function inspectFile(string $path, ?array $inputs = null): CompilationResult
    {
        $ptr = Native::lib()->typst_inspector_inspect_file(
            $this->handle,
            $path,
            Native::encodeInputs($inputs),
        );

        return CompilationResult::fromHandle(Native::requirePtr($ptr, 'inspectFile'));
    }

    public function clearCache(): int
    {
        return (int) Native::lib()->typst_inspector_clear_cache($this->handle);
    }

    public function getWorld(): World
    {
        $ptr = Native::lib()->typst_inspector_get_world($this->handle);

        return World::fromHandle(Native::requirePtr($ptr, 'getWorld'));
    }
}
