pdfexport exports pdfs utilizing pdftk and wkhtmltopdf.

## Features

- easy syntax
- can merge/grayscale/fill pdfs
- individual headers per page possible
- very fast (because of combining commands)

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
$_ENV['WKHTMLTOPDF'] = 'C:\wkhtmltopdf\bin\wkhtmltopdf.exe';
$_ENV['PDFTK'] = 'C:\pdftk\bin\pdftk.exe';
```

## Usage

$pdf = new PDFExport;

```php
// add a static pdf
$pdf->add('file.pdf');

// add a static pdf and fill out the form
$pdf->add('file.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder1' => 'bar'
    ]);

// do the same but grayscale the page
$pdf->add('file.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder1' => 'bar'
    ])
    ->grayscale();

// grayscale (not vector) with a resolution of 80%
$pdf->add('file.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder1' => 'bar'
    ])
    ->grayscale(80);

// add a html file
$pdf->add('file.html');

// add a html file and replace placeholders (%placeholder%)
$pdf->add('file.html')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// add a html file with a header and footer with a height of 30mm
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
$pdf->save();
$pdf->save('filename.pdf');
$pdf->base64();
```