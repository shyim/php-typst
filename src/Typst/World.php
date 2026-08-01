<?php

declare(strict_types=1);

namespace Typst;

use FFI;
use FFI\CData;
use Typst\Exception\InvalidArgumentException;

final class World
{
    private CData $handle;

    /**
     * @param list<non-empty-string>|null $font_dirs
     */
    public function __construct(
        ?string $template_dir = null,
        ?int $cache_size = null,
        ?bool $embed_default_fonts = null,
        ?array $font_dirs = null,
        ?string $package_dir = null,
    ) {
        if ($cache_size !== null && $cache_size < 0) {
            throw new InvalidArgumentException("Cache size must be >= 0, got {$cache_size}");
        }

        $ffi = Native::lib();
        $fontDirsJson = null;
        if ($font_dirs !== null) {
            $fontDirsJson = json_encode($font_dirs, JSON_THROW_ON_ERROR);
        }

        $ptr = $ffi->typst_world_new(
            $template_dir,
            $cache_size ?? -1,
            $embed_default_fonts === null ? -1 : ($embed_default_fonts ? 1 : 0),
            $fontDirsJson,
            $package_dir,
        );

        $this->handle = Native::requirePtr($ptr, 'World construction');
    }

    /** @internal */
    public static function fromHandle(CData $handle): self
    {
        $self = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $self->handle = $handle;

        return $self;
    }

    public function __destruct()
    {
        if (!FFI::isNull($this->handle)) {
            Native::lib()->typst_world_free($this->handle);
        }
    }

    public function __clone(): void
    {
        $ptr = Native::lib()->typst_world_clone($this->handle);
        $this->handle = Native::requirePtr($ptr, 'World clone');
    }

    /** @internal */
    public function handle(): CData
    {
        return $this->handle;
    }

    public function addFontData(string $data): void
    {
        [$buf, $len] = Native::stringToU8($data);
        Native::checkOk((int) Native::lib()->typst_world_add_font_data($this->handle, $buf, $len));
    }

    public function addFontFile(string $path): void
    {
        Native::checkOk((int) Native::lib()->typst_world_add_font_file($this->handle, $path));
    }

    public function loadString(string $source): Source
    {
        [$buf, $len] = Native::stringToU8($source);
        $ptr = Native::lib()->typst_world_load_string($this->handle, $buf, $len);

        return new Source(Native::requirePtr($ptr, 'loadString'));
    }

    public function loadFile(string $path): Source
    {
        $ptr = Native::lib()->typst_world_load_file($this->handle, $path);

        return new Source(Native::requirePtr($ptr, 'loadFile'));
    }

    /**
     * @return list<string>
     */
    public function getFontFamilies(): array
    {
        $json = Native::bufferToString(Native::lib()->typst_world_get_font_families($this->handle));
        if ($json === '' && (int) Native::lib()->typst_last_error_kind() !== 0) {
            Native::throwLastError();
        }

        /** @var list<string> */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        $json = Native::bufferToString(Native::lib()->typst_world_debug_info($this->handle));
        if ($json === '') {
            return [];
        }

        /** @var array<string, string> */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
