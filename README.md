# 🍊 pdfexport 🍊

pdfexport exports pdfs.

## Features

- easy syntax
- can merge/grayscale/fill pdfs
- individual headers per page possible
- very fast (because of combining commands)
- overcomes command line / process limits

## Requirements

- [pdftk](https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/)
- [wkhtmltopdf](https://wkhtmltopdf.org/)
- [ghostscript](https://www.ghostscript.com/)
- [imagemagick](https://www.imagemagick.org/)

## Installation

install once with composer:

```
composer require vielhuber/pdfexport
```

then add this to your files:

```php
require __DIR__.'/vendor/autoload.php';
use vielhuber\pdfexport\pdfexport;
```

you also can provide custom paths to underlying libs:

```php
$_ENV['PDFTK'] = 'C:\pdftk\bin\pdftk.exe';
$_ENV['WKHTMLTOPDF'] = 'C:\wkhtmltopdf\bin\wkhtmltopdf.exe';
$_ENV['GHOSTSCRIPT'] = 'C:\Program Files\GS\gs9.22\bin\gswin64c.exe';
$_ENV['IMAGEMAGICK'] = 'C:\Program Files\ImageMagick-6.9.9-Q16\convert.exe';
```

## Usage

```php
$pdf = new PDFExport;

// add a static pdf
$pdf->add('file.pdf');

// add a static pdf and fill out the form
$pdf->add('file.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// add multiple portions of data
$pdf->add('file.pdf')
    ->data([
        'placeholder1' => 'This is a test',
        'placeholder2' => 'This is a multiline\ntest1\ntest2\ntest3\ntest4\ntest5\ntest6\ntest7\ntest8\ntest9\ntest10'
    ])
    ->data([
        'placeholder3' => 'This is Sonderzeichen ß täst!'
    ]);

// do the same but grayscale the page
$pdf->add('file.pdf')
    ->grayscale();

// grayscale (not vector) with a resolution of 80%
$pdf->add('file.pdf')
    ->grayscale(80);

// add a html file
$pdf->add('file.html');

// add a html file and replace placeholders (%placeholder%)
$pdf->add('file.html')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// add a html file with a header and footer with a height of 30mm (there also can be placeholders in the header/footer)
$pdf->add('file.html')
    ->header('header.html', 30)
    ->footer('footer.html', 30)
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// strings are interpreted as html code
$pdf->add('<!DOCTYPE html><html><body><div>body with %placeholder1%</div></body></html>')
    ->header('<!DOCTYPE html><html><body><div style="height:30mm;">header with %placeholder2%</div></body></html>')
    ->footer('<!DOCTYPE html><html><body><div style="height:30mm;">footer with %placeholder3%</div></body></html>')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar',
        'placeholder3' => 'baz'
    ]);

// the cool part is that this is also very performant (because this results only in only a few subcommand)
foreach(range(0,5000) as $i)
{
    $pdf->add('file.html')
        ->header('header.html', 30)
        ->footer('footer.html', 30)
        ->data([
            'placeholder1' => 'foo',
            'placeholder2' => 'bar'
        ]);
}

$pdf->download();
$pdf->download('filename.pdf');
$pdf->save('filename.pdf');
$pdf->base64();
$random_filename = $pdf->save();
PDFExport::count($random_filename) // 1
```