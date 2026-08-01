<?php

declare(strict_types=1);

namespace Typst;

enum PdfValidator: string
{
    case A1b = 'a-1b';
    case A1a = 'a-1a';
    case A2b = 'a-2b';
    case A2u = 'a-2u';
    case A2a = 'a-2a';
    case A3b = 'a-3b';
    case A3u = 'a-3u';
    case A3a = 'a-3a';
    case A4 = 'a-4';
    case A4f = 'a-4f';
    case A4e = 'a-4e';
    case Ua1 = 'ua-1';
}
