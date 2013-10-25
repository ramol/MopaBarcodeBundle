<?php

namespace Mopa\Bundle\BarcodeBundle\Model;

use Monolog\Logger;
use Imagine\Gd\Image;
use Imagine\Image\ImagineInterface;
use Zend\Barcode\Barcode;
use Zend\Validator\Barcode as BarcodeValidator;

class BarcodeService
{

    private $types;
    private $imagine;
    private $kernelcachedir;
    private $kernelrootdir;
    private $webdir;
    private $webroot;
    private $logger;

    public function __construct(ImagineInterface $imagine, $kernelcachedir, $kernelrootdir, $webdir, $webroot, Logger $logger)
    {
        $this->types = BarcodeTypes::getTypes();
        $this->imagine = $imagine;
        $this->kernelcachedir = $kernelcachedir;
        $this->kernelrootdir = $kernelrootdir;
        $this->webdir = $webdir;
        $this->webroot = $webroot;
        $this->logger = $logger;
    }

    public function saveAs($type, $text, $file)
    {
        $fontfile = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "fonts" . DIRECTORY_SEPARATOR . 'Lato-Regular.ttf';
        @unlink($file);
        switch ($type)
        {
            case $type == 99:
                include_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "phpqrcode" . DIRECTORY_SEPARATOR . "qrlib.php";
                \QRcode::png($text, $file, QR_ECLEVEL_L, 12);
                break;
            case $type == 90:

                $font = new \Imagine\Gd\Font($fontfile, 35, new \Imagine\Image\Color('fff', 100));
                $resource = imagecreatetruecolor(2000, 60);
                $color = new \Imagine\Image\Color('fff');
                $white = imagecolorallocate($resource, 255, 255, 255);
                $black = imagecolorallocate($resource, 0, 0, 0);

                if (false === $white) {
                    throw new RuntimeException('Unable to allocate color');
                }

                if (false === imagefill($resource, 0, 0, $white)) {
                    throw new RuntimeException('Could not set background color fill');
                }
                imagettftext($resource, 35, 0, 10, 50, $black, $fontfile, $text);
                $image = new Image($resource);
                $image->crop(new \Imagine\Image\Point(0, 0), new \Imagine\Image\Box(20 + $font->box($text)->getWidth(), 60));
                $image->save($file);
                break;
            case is_numeric($type):
                $type = $this->types[$type];
            default:
                $validator = new BarcodeValidator(array(
                            'adapter' => $type,
                            'usechecksum' => false,
                        ));
//                if (!$validator->isValid($text)) {
//                    $message = implode("\n", $validator->getMessages());
//                    throw new \Symfony\Component\HttpKernel\Exception\HttpException(401, $message, null);
//                }
                //z apki dostaje barcody z 
//                if($type == 'ean13')
//                {
//                    $text = substr($text, 0, -1);
//                }

                $barcodeOptions = array('text' => $text, 'factor' => 3, 'font' => __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "fonts" . DIRECTORY_SEPARATOR . 'Lato-Regular.ttf');
                $rendererOptions = array();

                $imageRenderer = Barcode::factory(
                                $type, 'image', $barcodeOptions, $rendererOptions, false
                );
                
                //fix to not throw error when try to render barcode with code with checksum
                if($imageRenderer->getBarcode()->getWithChecksum())
                {
                    //maybe i got barcode without checksum need to test it by try cache :(
                    try {
                        $imageRenderer->getBarcode()->validateText($text);
                    }
                    catch (\Exception $exc) {
                        //propably length error remove checksum
                        //when barcode have mandatoryChecksum and have default 
                        //validateSpecificText then renderer waiting for code without checksum :(
                        $imageRenderer->getBarcode()->setText(substr($text, 0, -1));
                    }                    
                }

                //catch error and send http error 400 not 500 as default
                try {
                    $image = new Image(
                                    $imageRenderer->draw()
                    );
                }
                catch (\Exception $exc) {
                    $message =  $exc->getMessage();
                    throw new \Symfony\Component\HttpKernel\Exception\HttpException(400, $message, null);
                }

                $image->save($file);
        }
        return true;
    }

    /**
     * Get a Barcodes Filename
     * Generates it if its not here
     *
     * @param string $type BarcodeType
     * @param string $text BarcodeText
     * @param boolean $absolute get absolute path, default: false
     * @param array $options Options
     */
    public function get($type, $enctext, $absolut = false, $options = array())
    {
        $text = urldecode($enctext);
        $filename = $this->getAbsoluteBarcodeDir($type) . $this->getBarcodeFilename($text);
        if(
            (isset($options['noCache']) && $options['noCache'])
            || !file_exists($filename)
        ) {
            $this->saveAs($type, $text, $filename);
        }
        if (!$absolut) {
            $path = DIRECTORY_SEPARATOR . $this->webdir . $this->getTypeDir($type) . $this->getBarcodeFilename($text);
            return str_replace(DIRECTORY_SEPARATOR, "/", $path);
        }
        return $filename;
    }

    protected function getTypeDir($type)
    {
        if (is_numeric($type)) {
            $type = $this->types[$type];
        }
        return $type . DIRECTORY_SEPARATOR;
    }

    protected function getBarcodeFilename($text)
    {
        return sha1($text) . ".png";
    }

    protected function getAbsoluteBarcodeDir($type)
    {
        $path = $this->getAbsolutePath() . $this->getTypeDir($type);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    protected function getAbsolutePath()
    {
        return $this->webroot . DIRECTORY_SEPARATOR . $this->webdir;
    }

}
