<?php
namespace vielhuber\pdfexport;

class pdfexport
{

    private $data = [];
    private $settings = [];
    private $session = null;

    public function __construct()
    {
        file_put_contents( $this->filename('txt','log'),'');
        $this->session = uniqid();
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
                $content = getcwd().'/'.$content;
            }
            if(!file_exists($content))
            {
                die($content);
                throw new \Exception('file does not exist');
            }
            $this->data[] = [
                'type' => substr($content, strrpos($content, '.')+1),
                'filename' => $content
            ];
        }
        else
        {
            $filename = $this->filename('html');
            file_put_contents($filename, $content);
            $this->data[] = [
                'type' => 'html',
                'filename' => $filename
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

    public function header($content, $height = 30)
    {
        $this->header_footer('header', $content, $height);        
        return $this;
    }

    public function footer($content, $height = 30)
    {
        $this->header_footer('footer', $content, $height);
        return $this;
    }

    private function header_footer($type, $content, $height)
    {
        if( empty($this->data) )
        {
            throw new \Exception('you first need to add pages');
        }
        if( !in_array($type, ['header','footer']) )
        {
            throw new \Exception('wrong type');
        }
        if( $content === null || $content == '' )
        {
            throw new \Exception('content missing');
        }
        if( strrpos($content, '.html') === (mb_strlen($content)-mb_strlen('.html')) )
        {
            if(!file_exists($content))
            {
                $content = getcwd().'/'.$content;
            }
            if(!file_exists($content))
            {
                throw new \Exception('file does not exist');
            }
            $filename = $content;
        }
        else
        {
            $filename = $this->filename('html');
            file_put_contents($filename, $content);
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

    public function format($size = null, $orientation = null)
    {
        if( $size === null && $orientation === null )
        {
            throw new \Exception('missing size or orientation');
        }
        if( $size !== null && !in_array(mb_strtoupper($size), ['A0','A1','A2','A3','A4','A5','A6','A7','A8','A9','B0','B1','B2','B3','B4','B5','B6','B7','B8','B9','B10']) )
        {
            throw new \Exception('corrupt size');
        }
        if( $orientation !== null && !in_array(mb_strtolower($orientation), ['portrait','landscape']) )
        {
            throw new \Exception('corrupt orientation');
        }
        if( $this->data[count($this->data)-1]['type'] !== 'html' )
        {
            throw new \Exception('you only can a format to html files');
        }
        $format = [];
        if( $size !== null )
        {
            $format['size'] = $size;
        }
        if( $orientation !== null )
        {
            $format['orientation'] = $orientation;
        }
        $this->data[count($this->data)-1]['format'] = $format;
        return $this;
    }

    public function limit($pages)
    {
        if( empty($this->data) )
        {
            throw new \Exception('you first need to add pages');
        }
        if( $pages === null || !is_numeric($pages) || $pages < 0 )
        {
            throw new \Exception('corrupt pages');
        }
        $this->settings['limit'] = $pages;
        return $this;
    }

    public function setStandard($standard)
    {
        if( empty($this->data) )
        {
            throw new \Exception('you first need to add pages');
        }
        if( !in_array($standard, ['PDF/A']) )
        {
            throw new \Exception('unsupported standard');
        }
        $this->settings['standard'] = $standard;
        return $this;
    }

    public function disablePermission($permissions)
    {
        if( empty($this->data) )
        {
            throw new \Exception('you first need to add pages');
        }
        if( !is_array($permissions) || array_intersect($permissions, ['print','edit']) != $permissions )
        {
            throw new \Exception('unsupported permissions modes');
        }
        $this->settings['disabled_permissions'] = $permissions;
        return $this;
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
        header('Content-Disposition: inline; filename="'.$filename.'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.strlen(file_get_contents($this->filename('pdf','final')))); // filesize does not work on encrypted files(!)
        header('Accept-Ranges: bytes');
        readfile($this->filename('pdf','final'));
        die();
    }

    public function save($filename = null)
    {
        $this->process();
        if( $filename === null )
        {
            $filename = date('Y-m-d_H-i-s',strtotime('now')).'.pdf';
        }
        copy( $this->filename('pdf','final'), $filename );
        if( $filename === null )
        {
            return $filename;
        } 
        return true;
    }

    public function base64()
    {
        $this->process();
        return 'data:application/pdf;base64,'.base64_encode(file_get_contents($this->filename('pdf','final')));
    }

    public function content()
    {
        $this->process();
        return file_get_contents($this->filename('pdf','final'));
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
        $this->exec('pdftk', implode(' ',$files).' cat output '.$this->filename('pdf','final'));

        // limit 
        if( array_key_exists('limit', $this->settings) )
        {
            $target = $this->filename('pdf','final');
            $source = $this->filename('pdf');
            copy( $target, $source );
            $this->exec('cpdf', $source.' 1-'.$this->settings['limit'].' -o '.$target);
        }

        // standard
        if( array_key_exists('standard', $this->settings) )
        {
            $target = $this->filename('pdf','final');
            $source = $this->filename('pdf');
            copy( $target, $source );
            $this->exec('ghostscript', '-dPDFA -dBATCH -dNOPAUSE -sProcessColorModel=DeviceCMYK -sDEVICE=pdfwrite -dPDFACompatibilityPolicy=1 -sOutputFile='.$target.' '.$source);
        }

        // disabled permissions
        if( array_key_exists('disabled_permissions', $this->settings) )
        {
            $target = $this->filename('pdf','final');
            $source = $this->filename('pdf');
            copy( $target, $source );
            if( in_array('print',$this->settings['disabled_permissions']) && in_array('edit',$this->settings['disabled_permissions']) )
            {
                $allow = '';
            }
            else if( in_array('print',$this->settings['disabled_permissions']) )
            {
                $allow = ' allow ModifyContents allow CopyContents allow ModifyAnnotations';
            }
            else if( in_array('edit',$this->settings['disabled_permissions']) )
            {
                $allow = ' allow Printing';
            }
            $this->exec('pdftk', $source.' output '.$target.' owner_pw "'.md5(uniqid(mt_rand(), true)).'"'.$allow);
        }

    }

    private function generateAndRunNextMergedCommand(&$pointer, &$files)
    {

        // end of data
        if( !isset($this->data[$pointer]) )
        {
            return false;
        }

        $current = $this->data[$pointer];

        // a pdf without data
        if(
            $current['type'] === 'pdf' &&
            array_key_exists('filename',$current) && $current['filename'] != '' &&
            (!array_key_exists('data',$current) || empty($current['data']))
        )
        {
            copy( $current['filename'], $this->filename('pdf', $pointer) );
            $files[] = $this->filename('pdf', $pointer);
        }

        // a pdf with data
        if(
            $current['type'] === 'pdf' &&
            array_key_exists('filename',$current) && $current['filename'] != '' &&
            array_key_exists('data',$current) && !empty($current['data'])
        )
        {
            $fdf = [];
            $fdf[] = '%FDF-1.2';
            $fdf[] = '1 0 obj<</FDF<< /Fields[';
            foreach($current['data'] as $data__key=>$data__value)
            {
                $fdf[] = '<</T('.$data__key.')/V('.$data__value.')>>';
            }
            $fdf[] = '] >> >>';
            $fdf[] = 'endobj';
            $fdf[] = 'trailer';
            $fdf[] = '<</Root 1 0 R>>';
            $fdf[] = '%%EOF';
            $fdf = implode("\n",$fdf);
            file_put_contents($this->filename('fdf', $pointer), utf8_decode($fdf));
            $this->exec('pdftk', $current['filename'].' fill_form '.$this->filename('fdf', $pointer).' output '.$this->filename('pdf', $pointer).' flatten');
            $files[] = $this->filename('pdf', $pointer);
        }

        // html
        if( $current['type'] === 'html' )
        {
            // fetch as many as possible
            $fetched = [];            
            $loop = true;
            while($loop)
            {                
                if( !isset($this->data[$pointer]) )
                {
                    $loop = false;
                }
                elseif( $this->data[$pointer]['type'] !== 'html' )
                {
                    $loop = false;
                }
                // do not fetch more than 500 (enough is enough, especially on windows)
                elseif( count($fetched) > 500 )
                {
                    $loop = false;
                }
                elseif(
                    ( array_key_exists('grayscale', $current) && array_key_exists('grayscale', $this->data[$pointer]) && $current['grayscale'] != $this->data[$pointer]['grayscale'] ) ||
                    ( array_key_exists('grayscale', $current) && !array_key_exists('grayscale', $this->data[$pointer]) ) ||
                    ( !array_key_exists('grayscale', $current) && array_key_exists('grayscale', $this->data[$pointer]) )
                )
                {
                    $loop = false;
                }
                elseif(
                    ( array_key_exists('format', $current) && array_key_exists('format', $this->data[$pointer]) && $current['format'] != $this->data[$pointer]['format'] ) ||
                    ( array_key_exists('format', $current) && !array_key_exists('format', $this->data[$pointer]) ) ||
                    ( !array_key_exists('format', $current) && array_key_exists('format', $this->data[$pointer]) )
                )
                {
                    $loop = false;
                }
                elseif(
                    ( array_key_exists('header', $current) && array_key_exists('header', $this->data[$pointer]) && $current['header']['height'] != $this->data[$pointer]['header']['height'] ) ||
                    ( array_key_exists('header', $current) && !array_key_exists('header', $this->data[$pointer]) ) ||
                    ( !array_key_exists('header', $current) && array_key_exists('header', $this->data[$pointer]) )
                )
                {
                    $loop = false;
                }
                elseif(
                    ( array_key_exists('footer', $current) && array_key_exists('footer', $this->data[$pointer]) && $current['footer']['height'] != $this->data[$pointer]['footer']['height'] ) ||
                    ( array_key_exists('footer', $current) && !array_key_exists('footer', $this->data[$pointer]) ) ||
                    ( !array_key_exists('footer', $current) && array_key_exists('footer', $this->data[$pointer]) )
                )
                {
                    $loop = false;
                }
                else
                {
                    $fetched_this = [];
                    $fetched_this['body'] = $this->data[$pointer]['filename'];
                    if( array_key_exists('header', $this->data[$pointer]) )
                    {
                        $fetched_this['header'] = $this->data[$pointer]['header']['filename'];
                    }
                    if( array_key_exists('footer', $this->data[$pointer]) )
                    {
                        $fetched_this['footer'] = $this->data[$pointer]['footer']['filename'];
                    }                   
                    foreach($fetched_this as $fetched_this__key=>$fetched_this__value)
                    {
                        // placeholders
                        $data = [];
                        if( array_key_exists('data', $this->data[$pointer]) )
                        {
                            foreach($this->data[$pointer]['data'] as $data__key=>$data__value)
                            {
                                $data[$data__key] = $data__value;
                            }
                        }
                        $data = (object)$data;

                        // php code
                        ob_start();
                        include($fetched_this__value);
                        $content = ob_get_clean();

                        file_put_contents( $this->filename('html',$fetched_this__key.'_'.$pointer), $content );
                        $fetched_this[$fetched_this__key] = $this->filename('html',$fetched_this__key.'_'.$pointer);
                    }
                    $fetched[] = $fetched_this;
                    $pointer++;
                }
            }
            $pointer--;

            $command = '';
            $command .= '--disable-smart-shrinking ';
            if( array_key_exists('header', $current) )
            {
                $command .= '--margin-top "'.$current['header']['height'].'mm" ';
            }
            else
            {
                $command .= '--margin-top "0mm" ';
            }
            if( array_key_exists('footer', $current) )
            {
                $command .= '--margin-bottom "'.$current['footer']['height'].'mm" ';
            }
            else
            {
                $command .= '--margin-bottom "0mm" ';
            }
            $command .= '--margin-left "0mm" ';
            $command .= '--margin-right "0mm" ';
            if( array_key_exists('format', $current) && array_key_exists('orientation', $current['format']) )
            {
                $command .= '--orientation "'.ucfirst(mb_strtolower($current['format']['orientation'])).'" ';
            }
            else
            {
                $command .= '--orientation "Portrait" ';
            }
            if( array_key_exists('format', $current) && array_key_exists('size', $current['format']) )
            {
                $command .= '--page-size "'.mb_strtoupper($current['format']['size']).'" ';
            }
            else
            {
                $command .= '--page-size "A4" ';
            }
            $command .= '--quiet ';
            foreach($fetched as $fetched__value)
            {
                $command .= '"'.$fetched__value['body'].'" ';
                if( isset($fetched__value['header']) )
                {
                    $command .= '--header-html "'.$fetched__value['header'].'" ';
                }
                if( isset($fetched__value['footer']) )
                {
                    $command .= '--footer-html "'.$fetched__value['footer'].'" ';
                }
            }
            $command .= '"'.$this->filename('pdf', $pointer).'"';
            $this->exec('wkhtmltopdf', $command);
            $files[] = $this->filename('pdf', $pointer);
        }

        // grayscale
        if( array_key_exists('grayscale',$current) && !empty($current['grayscale']) )
        {
            $target = $files[count($files)-1];
            $source = $this->filename('pdf');
            copy( $target, $source );
            if( $current['grayscale']['type'] === 'vector' )
            {
                $this->exec('ghostscript', '-sOutputFile='.$target.' -sDEVICE=pdfwrite -sColorConversionStrategy=Gray -dProcessColorModel=/DeviceGray -dCompatibilityLevel=1.4 -dAutoRotatePages=/None -dNOPAUSE -dBATCH '.$source);
            }
            elseif( $current['grayscale']['type'] === 'pixel' )
            {
                $quality = $current['grayscale']['quality'];
                $density = 72+((300-72)*($quality/100));
                $this->exec('imagemagick', '-density '.$density.' '.$source.' -colorspace sRGB -colorspace GRAY '.$target); 
            }
        }

        $pointer++;

        return true;

    }

    private function exec($program, $command)
    {
        if( !in_array($program, ['wkhtmltopdf','pdftk','ghostscript','imagemagick','cpdf']) )
        {
            throw new \Exception('unknown program');
        }
        $run = '"';
        if( isset($_ENV[mb_strtoupper($program)]) && $_ENV[mb_strtoupper($program)] != '' )
        {
            $run .= str_replace('"','',$_ENV[mb_strtoupper($program)]);
        }
        else
        {
            $run .= [
                'wkhtmltopdf' => 'wkhtmltopdf',
                'pdftk' => 'pdftk',
                'ghostscript' => 'gs',
                'imagemagick' => 'convert',
                'cpdf' => 'cpdf'
            ][$program];
        }
        $run .= '"';
        $run .= ' ';
        // wkhtmltopdf provides a way to insert long commands via txt file
        if( $program === 'wkhtmltopdf' )
        {
            // replace backslash with forward slash
            $command = str_replace('\\','/',$command);
            file_put_contents( $this->filename('txt','command'), $command );
            $command = '--read-args-from-stdin < '.$this->filename('txt','command');
        }
        $run .= $command;
        $this->log($run);
        $ret = shell_exec($run);
        return $ret;
    }

    private function log($str)
    {
        if( !file_exists( $this->filename('txt','log') ) )
        {
            file_put_contents( $this->filename('txt','log'), '' );
        }
        file_put_contents($this->filename('txt','log'), $str."\n".file_get_contents($this->filename('txt','log')));
    }

    private function os()
    {
        if( stristr(PHP_OS, 'DAR') ) { return 'mac'; }
        if( stristr(PHP_OS, 'WIN') ) { return 'windows'; }
        if( stristr(PHP_OS, 'LINUX') ) { return 'linux'; }
        return 'unknown';
    }

    private function strposx($haystack, $needle)
    {
        $positions = [];
        $last_pos = 0;
        while(($last_pos = strpos($haystack, $needle, $last_pos)) !== false)
        {
            $positions[] = $last_pos;
            $last_pos += strlen($needle);
        }
        return $positions;
    }

    private function filename($extension, $id = null)
    {
        if( !in_array($extension, ['html','pdf','fdf','sh','bat','txt']) )
        {
            throw new \Exception('unknown extension');
        }
        if( $id === null )
        {
            $id = uniqid();
        }
        return sys_get_temp_dir().'/'.md5($this->session.'_'.$id).'.'.$extension;
    }

    public function count($filename)
    {
        if(!file_exists($filename))
        {
            $filename = getcwd().'/'.$filename;
        }
        if( !file_exists($filename) )
        {
            throw new \Exception('file does not exist');
        }
        $pages = $this->exec('pdftk', $filename.' dump_data | '.(($this->os()==='windows')?('findstr'):('grep')).' NumberOfPages');
        $pages = preg_replace('/[^0-9,.]/', '', $pages);
        $pages = intval($pages);
        return $pages;
    }

    public function split($filename, $chunksize = 1)
    {
        if(!file_exists($filename))
        {
            $filename = getcwd().'/'.$filename;
        }
        if( !file_exists($filename) )
        {
            throw new \Exception('file does not exist');
        }
        if( !is_numeric($chunksize) || $chunksize <= 0 )
        {
            throw new \Exception('corrupt chunksize');
        }
        $count = $this->count($filename);
        $this->exec('cpdf', '-split '.$filename.' -o '.str_replace('.pdf','',$filename).'-'.str_repeat('%',(log($count,10)+1)).'.pdf -chunk '.$chunksize);
        $filenames = [];
        // sometimes cpdf starts at 0
        if( file_exists( str_replace('.pdf','',$filename).'-'.str_pad(0, (log($count,10)+1), '0', STR_PAD_LEFT).'.pdf' ) )
        {
            $begin = 0;
            $end = $count-1;
        }
        else
        {
            $begin = 1;
            $end = $count;
        }
        while($begin <= $end)
        {

            $filenames[] = str_replace('.pdf','',$filename).'-'.str_pad($begin, (log($count,10)+1), '0', STR_PAD_LEFT).'.pdf';
            $begin++;
        }
        return $filenames;
    }

}