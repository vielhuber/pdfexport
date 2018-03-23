<?php
class PDFExport
{

    private $data = [];

    public function __construct()
    {
        file_put_contents('log.txt','');
    }

    public function add($content)
    {
        if( $content === null || $content == '' )
        {
            throw new \Exception('content missing');
        }
        if(
            strrpos($content, '.html') === (mb_strlen($content)-mb_strlen('.html')) ||
            strrpos($content, '.pdf') === (mb_strlen($content)-mb_strlen('.pdf'))
        )
        {
            if(!file_exists($content))
            {
                throw new \Exception('file does not exist');
            }
            $this->data[] = [
                'type' => substr($content, strrpos($content, '.')+1),
                'filename' => $content
            ];
        }
        else
        {
            $this->data[] = [
                'type' => 'html',
                'content' => $content
            ];
        }

        return $this;
    }

    public function data($data)
    {
        if( empty($this->data) )
        {
            throw new \Exception('you first need to add pages');
        }
        if( !array_key_exists('data', $this->data[count($this->data)-1]) )
        {
            $this->data[count($this->data)-1]['data'] = $data;
        }
        else
        {
            $this->data[count($this->data)-1]['data'] = array_merge($this->data[count($this->data)-1]['data'], $data);
        }
        return $this;
    }

    public function header($filename, $height = 30)
    {
        $this->header_footer('header', $filename, $height);        
        return $this;
    }

    public function footer($filename, $height = 30)
    {
        $this->header_footer('footer', $filename, $height);
        return $this;
    }

    private function header_footer($type, $filename, $height)
    {
        if( empty($this->data) )
        {
            throw new \Exception('you first need to add pages');
        }
        if( !in_array($type, ['header','footer']) )
        {
            throw new \Exception('wrong type');
        }
        if( !file_exists($filename) )
        {
            throw new \Exception($type.' file missing');
        }
        if( strrpos($filename, '.html') !== (mb_strlen($filename)-mb_strlen('.html')) )
        {
            throw new \Exception('you only can add html files as a '.$type);
        }
        if( $this->data[count($this->data)-1]['type'] !== 'html' )
        {
            throw new \Exception('you only can add '.$type.' to html files');
        }
        if( $height !== null && (!is_numeric($height) || $height < 0 ) )
        {
            throw new \Exception('corrupt height');
        }
        $this->data[count($this->data)-1][$type] = [
            'filename' => $filename,
            'height' => $height
        ];
    }

    public function grayscale($quality = null)
    {
        if( empty($this->data) )
        {
            throw new \Exception('you first need to add pages');
        }
        if( $quality !== null && (!is_numeric($quality) || $quality < 0 || $quality > 100 ) )
        {
            throw new \Exception('corrupt quality');
        }
        if( $quality === null )
        {
            $this->data[count($this->data)-1]['grayscale'] = [
                'type' => 'vector'
            ];
        }
        else
        {
            $this->data[count($this->data)-1]['grayscale'] = [
                'type' => 'pixel',
                'quality' => $quality
            ];
        }
        return $this;
    }

    public function download($filename = null)
    {
        $this->process();
        if( $filename === null )
        {
            $filename = date('Y-m-d_H-i-s',strtotime('now')).'.pdf';
        }
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="'.$filename.'.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($this->filename('final.pdf')));
        header('Accept-Ranges: bytes');
        readfile($this->filename('final.pdf'));
        die();
    }

    public function save($filename = null)
    {
        $this->process();
        if( $filename === null )
        {
            $filename = date('Y-m-d_H-i-s',strtotime('now')).'.pdf';
        }
        copy( $this->filename('final.pdf'), $filename );
        if( $filename === null )
        {
            return $filename;
        } 
        return true;
    }

    public function base64()
    {
        $this->process();
        return base64_encode($this->filename('final.pdf'));
    }

    public function debug()
    {
        $this->process();
        echo '<hr/>';
        echo '<hr/>';
        echo '<pre>';
        array_walk_recursive($this->data, function(&$v) { $v = htmlspecialchars($v); });
        var_dump($this->data);
        echo '</pre>';
        die();
    }

    private function process()
    {
        $commands = [];
        $files = [];
        $pointer = 0;
        while( $this->generateAndRunNextMergedCommand($pointer, $files) ) {}
        $this->exec('pdftk', implode(' ',$files).' cat output '.$this->filename('final.pdf'));
    }

    private function generateAndRunNextMergedCommand(&$pointer, &$files)
    {

        // end of data
        if( !isset($this->data[$pointer]) )
        {
            return false;
        }

        // a pdf without data
        if(
            $this->data[$pointer]['type'] === 'pdf' &&
            array_key_exists('filename',$this->data[$pointer]) && $this->data[$pointer]['filename'] != '' &&
            (!array_key_exists('data',$this->data[$pointer]) || empty($this->data[$pointer]['data']))
        )
        {
            $filename = $this->filename('pdf');
            copy( $this->data[$pointer]['filename'], $filename );
            $files[] = $filename;
        }

        // a pdf with data
        

        // finally: grayscale
        if( array_key_exists('grayscale',$this->data[$pointer]) && !empty($this->data[$pointer]['grayscale']) )
        {
            $target = $files[count($files)-1];
            $source = $this->filename('pdf');
            copy( $target, $source );
            if( $this->data[$pointer]['grayscale']['type'] === 'vector' )
            {
                $this->exec('ghostscript', '-sOutputFile='.$target.' -sDEVICE=pdfwrite -sColorConversionStrategy=Gray -dProcessColorModel=/DeviceGray -dCompatibilityLevel=1.4 -dAutoRotatePages=/None -dNOPAUSE -dBATCH '.$source);
            }
            elseif( $this->data[$pointer]['grayscale']['type'] === 'pixel' )
            {
                $quality = $this->data[$pointer]['grayscale']['quality'];
                $density = 72+((300-72)*($quality/100));
                $this->exec('imagemagick', '-density '.$density.' '.$source.' -colorspace GRAY '.$target); 
            }
        }

        $pointer++;

        return true;

        /*
        $filename = $this->filename('pdf');
        copy( 'tests/1.pdf', $filename );
        $files[] = $filename;

        $filename = $this->filename('pdf');
        copy( 'tests/1.pdf', $filename );
        $files[] = $filename;
        */

    }

    private function exec($program, $command)
    {
        if( !in_array($program, ['wkhtmltopdf','pdftk','ghostscript','imagemagick']) )
        {
            throw new \Exception('unknown program');
        }
        $run = '"';
        if( isset($_ENV[mb_strtoupper($program)]) && $_ENV[mb_strtoupper($program)] != '' )
        {
            $run .= $_ENV[mb_strtoupper($program)];
        }
        else
        {
            $run .= [
                'wkhtmltopdf' => 'wkhtmltopdf',
                'pdftk' => 'pdftk',
                'ghostscript' => 'gs',
                'imagemagick' => 'convert'
            ][$program];
        }
        $run .= '"'.' '.$command;
        exec($run);
        $this->log($run);
    }

    private function log($str)
    {
        if( !file_exists('log.txt') )
        {
            file_put_contents('log.txt','');
        }
        file_put_contents('log.txt',$str."\n".file_get_contents('log.txt'));
    }

    private function filename($str = null)
    {
        if( $str === null )
        {
            return sys_get_temp_dir().'/'.md5(uniqid());
        }
        if( $str === 'pdf' || $str === 'html' )
        {
            return sys_get_temp_dir().'/'.md5(uniqid()).'.'.$str;
        }
        if( strrpos($str, '.html') === (mb_strlen($str)-mb_strlen('.html')) )
        {
            return sys_get_temp_dir().'/'.md5($str).'.html';
        }
        if( strrpos($str, '.pdf') === (mb_strlen($str)-mb_strlen('.pdf')) )
        {
            return sys_get_temp_dir().'/'.md5($str).'.pdf';
        }
        throw new \Exception('cannot create filename');
    }

}

$_ENV['WKHTMLTOPDF'] = 'C:\wkhtmltopdf\bin\wkhtmltopdf.exe';
$_ENV['PDFTK'] = 'C:\pdftk\bin\pdftk.exe';
$_ENV['GHOSTSCRIPT'] = 'C:\Program Files\GS\gs9.22\bin\gswin64c.exe';
$_ENV['IMAGEMAGICK'] = 'C:\Program Files\ImageMagick-6.9.9-Q16\convert.exe';

$pdf = new PDFExport;

$pdf->add('tests/1.pdf');

$pdf->add('tests/1.pdf');

$pdf->add('tests/1.pdf')->grayscale();

$pdf->add('tests/1.pdf')->grayscale(10);

$pdf->add('tests/1.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder2' => 'bar'
    ])
    ->data([
        'placeholder1' => 'foo',
        'placeholder3' => 'bar'
    ]);

$pdf->add('tests/1.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder3' => 'bar'
    ]);

$pdf->add('tests/1.html');

$pdf->add('<html><head><title>.</title></head><body>foo</body></html>');

$pdf->add('tests/1.html')
    ->header('tests/header.html')
    ->footer('tests/footer.html')
    ->data([
        'placeholder1' => 'foo',
        'placeholder3' => 'bar'
    ]);

$pdf->add('tests/1.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder3' => 'bar'
    ])
    ->grayscale();

$pdf->add('tests/1.pdf')
    ->data([
        'placeholder1' => 'foo',
        'placeholder3' => 'bar'
    ])
    ->grayscale(50);

$pdf->download();
$pdf->debug();