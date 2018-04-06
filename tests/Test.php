<?php
use vielhuber\pdfexport\pdfexport;

class Test extends \PHPUnit\Framework\TestCase
{
  
    function test__pdfexport()
    {
        $pdf = new pdfexport;
        $pdf->add('tests/file.pdf');
        $pdf->add('tests/file.pdf')
            ->data([
                'placeholder1' => 'foo',
                'placeholder2' => 'bar'
            ]);
        $pdf->add('tests/file.pdf')
            ->data([
                'placeholder1' => 'This is a test',
                'placeholder2' => 'This is a multiline\ntest1\ntest2\ntest3\ntest4\ntest5\ntest6\ntest7\ntest8\ntest9\ntest10'
            ])
            ->data([
                'placeholder3' => 'This is Sonderzeichen ß täst!'
            ]);
        $pdf->add('tests/file.pdf')
            ->grayscale();
        $pdf->add('tests/file.pdf')
            ->grayscale(80);
        $pdf->add('tests/file.html');
        $pdf->add('tests/file.html')
            ->format('a3','landscape');
        $pdf->add('tests/file.html')
            ->data([
                'placeholder1' => 'foo',
                'placeholder2' => 'bar'
            ]);
        $pdf->add('tests/file.html')
            ->header('tests/header.html', 30)
            ->footer('tests/footer.html', 30)
            ->data([
                'placeholder1' => 'foo',
                'placeholder2' => 'bar'
            ]);
        $pdf->add('<!DOCTYPE html><html><body><div>body with %placeholder1%</div></body></html>')
            ->header('<!DOCTYPE html><html><body><div style="height:30mm;">header with %placeholder2%</div></body></html>')
            ->footer('<!DOCTYPE html><html><body><div style="height:30mm;">footer with %placeholder3%</div></body></html>')
            ->data([
                'placeholder1' => 'foo',
                'placeholder2' => 'bar',
                'placeholder3' => 'baz'
            ]);
        $pdf->add('<!DOCTYPE html><html><body><div>current time: <?php echo date(\'Y-m-d\'); ?></div></body></html>');
        foreach(range(0,1500) as $i)
        {
            $pdf->add('tests/file.html')
                ->header('tests/header.html', 30)
                ->footer('tests/footer.html', 30)
                ->data([
                    'placeholder1' => 'foo',
                    'placeholder2' => 'bar'
                ]);
        }
        $pdf->save('tests/output.pdf');
        $this->assertEquals( $pdf->count('tests/output.pdf'), 1512 );

        $pdf->split('tests/output.pdf', 1);
        $dir = new DirectoryIterator('tests/');
        $split_count = 0;
        foreach($dir as $dir__value)
        {
            if(!$dir__value->isDot() && strpos($dir__value->getFilename(), '-') !== false)
            {
                @unlink($dir__value->getPathname());
                $split_count++;
            }
        }
        $this->assertEquals( $split_count, 1512 );

    }

}