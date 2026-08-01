<?php

declare(strict_types=1);

namespace Typst;

use FFI;
use FFI\CData;

final class Compiler
{
    private CData $handle;

    public function __construct(World $world)
    {
        $ptr = Native::lib()->typst_compiler_new($world->handle());
        $this->handle = Native::requirePtr($ptr, 'Compiler construction');
    }

    public function __destruct()
    {
        if (isset($this->handle) && !FFI::isNull($this->handle)) {
            Native::lib()->typst_compiler_free($this->handle);
        }
    }

    public function __clone(): void
    {
        $world = $this->getWorld();
        $ptr = Native::lib()->typst_compiler_new($world->handle());
        $this->handle = Native::requirePtr($ptr, 'Compiler clone');
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public function compile(Source $source, ?array $inputs = null): Document
    {
        $ptr = Native::lib()->typst_compiler_compile(
            $this->handle,
            $source->handle(),
            Native::encodeInputs($inputs),
        );

        return new Document(Native::requirePtr($ptr, 'compile'));
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public function compileString(string $source, ?array $inputs = null): Document
    {
        [$buf, $len] = Native::stringToU8($source);
        $ptr = Native::lib()->typst_compiler_compile_string(
            $this->handle,
            $buf,
            $len,
            Native::encodeInputs($inputs),
        );

        return new Document(Native::requirePtr($ptr, 'compileString'));
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public function compileFile(string $path, ?array $inputs = null): Document
    {
        $ptr = Native::lib()->typst_compiler_compile_file(
            $this->handle,
            $path,
            Native::encodeInputs($inputs),
        );

        return new Document(Native::requirePtr($ptr, 'compileFile'));
    }

    /**
     * @param array<string, mixed>|null $inputs
     */
    public function compileInBackground(Source $source, ?array $inputs = null): PendingDocument
    {
        $ptr = Native::lib()->typst_compiler_compile_in_background(
            $this->handle,
            $source->handle(),
            Native::encodeInputs($inputs),
        );

        return new PendingDocument(Native::requirePtr($ptr, 'compileInBackground'));
    }

    public function clearCache(): int
    {
        return (int) Native::lib()->typst_compiler_clear_cache($this->handle);
    }

    public function getWorld(): World
    {
        $ptr = Native::lib()->typst_compiler_get_world($this->handle);

        return World::fromHandle(Native::requirePtr($ptr, 'getWorld'));
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        $json = Native::bufferToString(Native::lib()->typst_compiler_debug_info($this->handle));
        if ($json === '') {
            return [];
        }

        /** @var array<string, string> */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
