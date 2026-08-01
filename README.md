# php-typst

PHP bindings for the [Typst](https://typst.app/) typesetting engine via **FFI**.

The native library is the prebuilt **[shyim/typst-ffi](https://github.com/shyim/typst-ffi)** release asset for your platform, installed automatically by
[`shyim/composer-binary-downloader`](https://github.com/shyim/composer-binary-downloader).

No PHP extension build. No local Rust toolchain required for consumers.

## Install

```bash
composer require shyim/php-typst
```

Approve the plugin (once per project):

```json
{
  "config": {
    "allow-plugins": {
      "shyim/composer-binary-downloader": true
    }
  }
}
```

If Composer prompts about **allow-binaries** for this package, accept it so the
native library can be downloaded from GitHub Releases.

Requires:

- PHP 8.3+
- `ext-ffi`
- Network access to GitHub Releases on first install (or prefetch in CI)

## Quick start

```php
$world = new Typst\World();
$compiler = new Typst\Compiler($world);

$document = $compiler->compileString(<<<'TYPST'
#set page(height: auto)
= Hello from Typst

This is a *bold* statement with _italic_ flair.
TYPST);

$document->toPdf()->save('output.pdf');
$document->toImage()->save('output.png');
$document->toSvg()->save('output.svg');
```

## How the binary is resolved

Configured in `composer.json` → `extra.binaries.typst`:

| Item | Value |
|------|--------|
| Source | `shyim/typst-ffi` GitHub Releases |
| Version | `0.1.0` (bump when adopting a new typst-ffi release) |
| Asset | `typst-ffi-v{version}-{target}.tar.xz` (`.zip` on Windows) |
| Install path | `vendor/shyim/php-typst/bin/<target>/libtypst_ffi.{so,dylib}` |

At runtime:

```php
\Shyim\BinaryDownloader\Binaries::path('typst');
// → …/vendor/shyim/php-typst/bin/aarch64-apple-darwin/libtypst_ffi.dylib
```

### Commands

```bash
composer binary:install typst          # host platform
composer binary:install typst -t all   # prefetch all targets (image builds)
composer binary:list
```

### Environment overrides

| Variable | Effect |
|----------|--------|
| `TYPST_LIBRARY` | Use this shared library path; skip download |
| `TYPST_TARGET` | Force a target triple |
| `TYPST_SKIP_DOWNLOAD` | Do not download |
| `TYPST_DOWNLOAD_BASE_URL` | Mirror base URL |

## API (mirrors ext-typst)

| Class / function | Role |
|------------------|------|
| `Typst\version()` / `Typst\typst_version()` | Package / engine versions |
| `Typst\World` | Fonts, template dir, sources |
| `Typst\Compiler` | Compile (throws on error) |
| `Typst\Inspector` | Compile with diagnostics |
| `Typst\Document` | Export PDF / PNG / JPEG / SVG |
| `Typst\PendingDocument` | Background compile (+ notification FD on Unix) |

## Development

```bash
composer install
composer binary:install typst
vendor/bin/phpunit
php examples/hello.php
```

To use a local build of typst-ffi instead of a release:

```bash
export TYPST_LIBRARY=/path/to/libtypst_ffi.dylib
```

## License

MIT OR Apache-2.0
