<?php

declare(strict_types=1);

/**
 * German commercial invoice example.
 *
 * Layout mirrors Shopware document templates (letter header, line-item table,
 * tax summary, payment/shipping block, multi-column footer) from:
 *   Framework/Resources/views/documents/{base,invoice}.html.twig
 *   Framework/Resources/views/documents/includes/*
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$invoice = [
    'currency' => '€',
    'seller' => [
        'name' => 'Muster GmbH',
        'street' => 'Beispielstraße 12',
        'zip' => '80331',
        'city' => 'München',
        'addressLine' => 'Muster GmbH · Beispielstraße 12 · 80331 München',
        'phone' => '+49 89 123456-0',
        'email' => 'rechnung@muster-gmbh.de',
        'url' => 'www.muster-gmbh.de',
        'taxNumber' => '143/123/12345',
        'vatId' => 'DE123456789',
        'taxOffice' => 'München',
        'ceo' => 'Erika Mustermann',
        'placeOfJurisdiction' => 'München',
        'placeOfFulfillment' => 'München',
        'bank' => [
            'name' => 'Musterbank AG',
            'iban' => 'DE89 3704 0044 0532 0130 00',
            'bic' => 'COBADEFFXXX',
        ],
    ],
    'buyer' => [
        'company' => 'Beispiel AG',
        'department' => 'Einkauf',
        'firstName' => 'Max',
        'lastName' => 'Schmidt',
        'street' => 'Hauptstraße 1',
        'zip' => '10115',
        'city' => 'Berlin',
        'country' => 'Deutschland',
        'vatId' => 'DE987654321',
    ],
    'shipping' => [
        'different' => true,
        'company' => 'Beispiel AG · Lager Nord',
        'firstName' => 'Max',
        'lastName' => 'Schmidt',
        'street' => 'Logistikweg 7',
        'zip' => '20095',
        'city' => 'Hamburg',
        'country' => 'Deutschland',
    ],
    'meta' => [
        'invoiceNumber' => '10045',
        'customerNumber' => 'K-2048',
        'orderNumber' => 'SW-90012',
        'orderDate' => '15.03.2026',
        'invoiceDate' => '16.03.2026',
    ],
    'lineItems' => [
        [
            'productNumber' => 'SW-1000',
            'label' => 'Ergonomischer Bürostuhl „Focus“',
            'options' => 'Farbe: Graphit | Stoff: Mesh',
            'quantity' => 2,
            'taxRate' => 19,
            'unitPrice' => 299.00,
            'totalPrice' => 598.00,
        ],
        [
            'productNumber' => 'SW-2042',
            'label' => 'Höhenverstellbarer Schreibtisch 160×80',
            'options' => 'Gestell: Weiß | Platte: Eiche',
            'quantity' => 1,
            'taxRate' => 19,
            'unitPrice' => 649.00,
            'totalPrice' => 649.00,
        ],
        [
            'productNumber' => 'SW-3310',
            'label' => 'Monitorarm Dual',
            'options' => null,
            'quantity' => 1,
            'taxRate' => 19,
            'unitPrice' => 89.90,
            'totalPrice' => 89.90,
        ],
        [
            'productNumber' => 'SW-SHIP',
            'label' => 'Versandkosten',
            'options' => null,
            'quantity' => 1,
            'taxRate' => 19,
            'unitPrice' => 0.00,
            'totalPrice' => 0.00,
        ],
    ],
    'totals' => [
        // Gross prices in line items; net/tax derived for German B2C-style invoice
        'net' => 1123.45,   // 1336.90 / 1.19
        'taxes' => [
            ['rate' => 19, 'amount' => 213.45],
        ],
        'gross' => 1336.90,
    ],
    'payment' => [
        'method' => 'Rechnung (14 Tage netto)',
    ],
    'shippingMethod' => 'DHL Paket',
    'notes' => 'Vielen Dank für Ihren Auftrag. Bei Fragen erreichen Sie uns unter rechnung@muster-gmbh.de.',
];

$world = new Typst\World(template_dir: __DIR__ . '/templates');
$compiler = new Typst\Compiler($world);

// Pass structured invoice data as JSON via sys.inputs (Typst json() + bytes()).
$document = $compiler->compileFile('invoice.typ', [
    'invoice' => json_encode($invoice, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
]);

$out = __DIR__ . '/output';
if (!is_dir($out)) {
    mkdir($out, 0777, true);
}

// PDF/UA-1 (ISO 14289-1): tagged structure tree for assistive tech.
// See Typst\PdfValidator::Ua1 — requires tagged PDF (default true).
$pdfUa = new Typst\PdfOptions(
    identifier: 'invoice-' . $invoice['meta']['invoiceNumber'],
    timestamp: (new DateTimeImmutable($invoice['meta']['invoiceDate'] . ' 12:00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp(),
    version: Typst\PdfVersion::V1_7,
    validator: Typst\PdfValidator::Ua1,
    tagged: true,
);

$pdfPath = $out . '/rechnung.pdf';
$pdfUaPath = $out . '/rechnung-ua.pdf';

$document->toPdf()->save($pdfPath);
$document->toPdf($pdfUa)->save($pdfUaPath);
$document->toImage(null, new Typst\ImageOptions(dpi: 144.0))->save($out . '/rechnung.png');

echo "German invoice written to:\n";
echo "  {$pdfPath}     (default PDF 1.7, tagged)\n";
echo "  {$pdfUaPath}  (PDF/UA-1)\n";
echo "  {$out}/rechnung.png\n";
echo 'Pages: ' . $document->pageCount() . PHP_EOL;
echo 'PDF/UA size: ' . filesize($pdfUaPath) . " bytes\n";
