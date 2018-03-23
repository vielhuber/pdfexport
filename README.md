pdfexport exports pdfs.

## Features

- easy syntax
- can merge/grayscale/fill pdfs
- individual headers per page possible
- very fast (because of combining commands)

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

// strings are interpreted as html code
$pdf->add('<html><head><title>.</title></head><body>foo</body></html>');

// add a html file and replace placeholders (%placeholder%)
$pdf->add('file.html')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// add a html file with a header and footer with a height of 30mm (there also can be placeholders in the header/footer!)
$pdf->add('file.html')
    ->header('header.html', 30)
    ->footer('footer.html', 30)
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// the cool part is that this is also very performant (because it results only in 1 subcommand)
foreach(range(0,1000) as $i)
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
$random_filename = $pdf->save();
$pdf->base64();
```