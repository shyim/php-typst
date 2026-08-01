<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$world = new Typst\World();
$compiler = new Typst\Compiler($world);

$source = $world->loadString(<<<'TYPST'
    #set page(height: auto)
    = Hello from Typst (FFI)

    This is a *bold* statement with _italic_ flair.
    TYPST);

$document = $compiler->compile($source);

$out = __DIR__ . '/output';
if (!is_dir($out)) {
    mkdir($out, 0777, true);
}

$document->toPdf()->save($out . '/hello.pdf');
$document->toImage()->save($out . '/hello.png');
$document->toSvg()->save($out . '/hello.svg');

echo 'Library version: ' . Typst\version() . PHP_EOL;
echo 'Typst engine:    ' . Typst\typst_version() . PHP_EOL;
echo "Native library:  " . Typst\Native::discoverLibraryPath() . PHP_EOL;
echo "Exported hello.pdf, hello.png, hello.svg\n";
