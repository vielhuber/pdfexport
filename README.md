[![build status](https://github.com/vielhuber/pdfexport/actions/workflows/ci.yml/badge.svg)](https://github.com/vielhuber/pdfexport/actions)

# 🍊 pdfexport 🍊

pdfexport exports pdfs.

## Features

-   easy syntax
-   can merge/grayscale/fill pdfs
-   individual headers per page possible
-   very fast because of combining commands
-   overcomes command line / process limits
-   allows php code inside html templates
-   counts pages of pdfs
-   can set a limit on page counts
-   splits pdfs in chunks of size n
-   can create pdf/a files
-   can disallow printing and editing
-   stamp/watermark pdfs

## Requirements

-   [pdftk](https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/)
-   [wkhtmltopdf](https://wkhtmltopdf.org/)
-   [ghostscript](https://www.ghostscript.com/)
-   [imagemagick](https://www.imagemagick.org/)
-   [cpdf](http://community.coherentpdf.com/)

## Installation

install once with [composer](https://getcomposer.org/):

```
composer require vielhuber/pdfexport
```

then add this to your files:

```php
require __DIR__ . '/vendor/autoload.php';
use vielhuber\pdfexport\pdfexport;
```

you also can provide custom paths to underlying libs:

```php
$_ENV['PDFTK'] = 'C:\pdftk\bin\pdftk.exe';
$_ENV['WKHTMLTOPDF'] = 'C:\wkhtmltopdf\bin\wkhtmltopdf.exe';
$_ENV['GHOSTSCRIPT'] = 'C:\Program Files\GS\gs9.22\bin\gswin64c.exe';
$_ENV['IMAGEMAGICK'] = 'C:\Program Files\ImageMagick-6.9.9-Q16\convert.exe';
$_ENV['CPDF'] = 'C:\Program Files\cpdf\cpdf.exe';
```

in [laravel](https://www.laravel.org) just populate .env:

```php
PDFTK="C:\pdftk\bin\pdftk.exe"
WKHTMLTOPDF="C:\wkhtmltopdf\bin\wkhtmltopdf.exe"
GHOSTSCRIPT="C:\Program Files\GS\gs9.22\bin\gswin64c.exe"
IMAGEMAGICK="C:\Program Files\ImageMagick-6.9.9-Q16\convert.exe"
CPDF="C:\Program Files\cpdf\cpdf.exe"
```

and can overcome \*nix limits by increasing the ulimit for open files:

```
ulimit -n 999999
```

you can do this permanently inside /etc/security/limits.conf:

```
* - nofile 999999
```

## Usage

```php
$pdf = new pdfexport;

// add a static pdf
$pdf->add('tests/file.pdf');

// add a static pdf and fill out the form
$pdf->add('tests/file.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// add multiple portions of data
$pdf->add('tests/file.pdf')
    ->data([
        'placeholder1' => 'This is a test',
        'placeholder2' => 'This is a multiline\ntest1\ntest2\ntest3\ntest4\ntest5\ntest6\ntest7\ntest8\ntest9\ntest10'
    ])
    ->data([
        'placeholder3' => 'This is Sonderzeichen ß täst!'
    ]);

// form fields
$pdf->getFormFields('tests/file.pdf'); // [ ['name' => 'field_name_1', 'type' => 'Text'], ... ]
$pdf->hasFormField('tests/file.pdf', 'field_name_1') // true

// do the same but grayscale the page
$pdf->add('tests/file.pdf')
    ->grayscale();

// grayscale (not vector) with a resolution of 80%
$pdf->add('tests/file.pdf')
    ->grayscale(80);

// add a html file
$pdf->add('tests/file.html');

// add a html file in a3, landscape
$pdf->add('tests/file.html')
    ->format('a3','landscape');

// add a html file and replace placeholders (<?php echo $data->placeholder; ?>)
$pdf->add('tests/file.html')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// add a html file with a header and footer with a height of 30mm (there also can be placeholders in the header/footer)
$pdf->add('tests/file.html')
    ->header('tests/header.html', 30)
    ->footer('tests/footer.html', 30)
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ]);

// strings are interpreted as html code
$pdf->add('<!DOCTYPE html><html><body><div>body with <?php echo $data->placeholder1; ?></div></body></html>')
    ->header('<!DOCTYPE html><html><body><div style="height:30mm;">header with <?php echo $data->placeholder2; ?></div></body></html>')
    ->footer('<!DOCTYPE html><html><body><div style="height:30mm;">footer with <?php echo $data->placeholder3; ?></div></body></html>')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar',
        'placeholder3' => 'baz'
    ]);

// you can also execute php code inside templates
$pdf->add('<!DOCTYPE html><html><body><div>cur time: <?php echo date(\'Y-m-d\'); ?></div></body></html>');
$pdf->add('<!DOCTYPE html><html><body><div>another part: <?php include(\'tests/part.html\'); ?></div></body></html>');

// the cool part is that this is also very performant (because this results only in only a few subcommands)
foreach(range(0,2500) as $i)
{
    $pdf->add('tests/file.html')
        ->header('tests/header.html', 30)
        ->footer('tests/footer.html', 30)
        ->data([
            'placeholder1' => 'foo',
            'placeholder2' => 'bar'
        ]);
}

// put stamp on all pages
$pdf->stamp('tests/watermark.pdf');

$pdf->download();
$pdf->download('tests/output.pdf');

$pdf->save('tests/output.pdf');

$pdf->base64();
$pdf->content();

$random_filename = $pdf->save();

$pdf->count($random_filename); // 42

$splitted_filenames = $pdf->split($random_filename, 7) // splits pdf in 6 chunks of size 7

$pdf->add('<!DOCTYPE html><html><body><div style="height:8000px;"></div></body></html>')
    ->limit(2)
    ->save('tests/output.pdf');
$pdf->count('tests/output.pdf'); // 2;

$pdf->add('<!DOCTYPE html><html><body><div></div></body></html>')
    ->setStandard('PDF/A')
    ->disablePermission(['print','edit'])
    ->save('tests/output.pdf');
$pdf->count('tests/output.pdf'); // 0 (no permission!)

```
