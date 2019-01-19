<?php
use vielhuber\pdfexport\pdfexport;

class Test extends \PHPUnit\Framework\TestCase
{
  
    function test__pdfexport()
    {
        // run test multiple times
        for($test_iteration = 0; $test_iteration < 10; $test_iteration++)
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
                    'placeholder3' => 'This is Sonderzeichen ß täst! with enclosing bracket ('
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
            $pdf->add('<!DOCTYPE html><html><body><div>body with <?php echo $data->placeholder1; ?></div></body></html>')
                ->header('<!DOCTYPE html><html><body><div style="height:30mm;">header with <?php echo $data->placeholder2; ?></div></body></html>')
                ->footer('<!DOCTYPE html><html><body><div style="height:30mm;">footer with <?php echo $data->placeholder3; ?></div></body></html>')
                ->data([
                    'placeholder1' => 'foo',
                    'placeholder2' => 'bar',
                    'placeholder3' => 'baz'
                ]);
            $pdf->add('<!DOCTYPE html><html><body><div>current time: <?php echo date(\'Y-m-d\'); ?></div></body></html>');

            $limit = mt_rand(15,3000);
            foreach(range(0,$limit) as $i)
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
            $this->assertEquals( $pdf->count('tests/output.pdf'), ($limit+12) );

            $splitted_filenames = $pdf->split('tests/output.pdf', 1);
            $this->assertEquals( count($splitted_filenames), ($limit+12) );
            $this->assertEquals( in_array($splitted_filenames[0], ['tests/output-'.str_pad(0, (log(count($splitted_filenames),10)+1), '0', STR_PAD_LEFT).'.pdf','tests/output-'.str_pad(1, (log(count($splitted_filenames),10)+1), '0', STR_PAD_LEFT).'.pdf']), true );
            $this->assertEquals( in_array($splitted_filenames[($limit+12)-1], ['tests/output-'.($limit+12-1).'.pdf','tests/output-'.($limit+12).'.pdf']), true );
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
            $this->assertEquals( $split_count, ($limit+12) );

            $pdf = new pdfexport;
            $pdf->add('<!DOCTYPE html><html><body><div style="height:8000px;"></div></body></html>')
                ->limit(2)
                ->save('tests/output.pdf');
            $this->assertEquals( $pdf->count('tests/output.pdf'), 2 );

            $pdf = new pdfexport;
            $pdf->add('<!DOCTYPE html><html><body><div>Cool!</div></body></html>')
                ->setStandard('PDF/A')
                ->save('tests/output.pdf');
            $this->assertEquals( $pdf->count('tests/output.pdf'), 1 );

            $pdf = new pdfexport;
            $pdf->add('<!DOCTYPE html><html><body><div>Cool!</div></body></html>')
                ->disablePermission(['print','edit'])
                ->save('tests/output.pdf');
            //$this->assertEquals( $pdf->count('tests/output.pdf'), 1 );

            $pdf = new pdfexport;
            $pdf->add('<!DOCTYPE html><html><body><div>Cool!</div></body></html>')
                ->setStandard('PDF/A')
                ->disablePermission(['print','edit'])
                ->save('tests/output.pdf');
            //$this->assertEquals( $pdf->count('tests/output.pdf'), 1 );

            fwrite(STDERR, print_r('correctly done loop '.$test_iteration.' with a '.($limit+12).'-paged pdf'.PHP_EOL, true));
        }

    }

}