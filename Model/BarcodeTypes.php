<?php
namespace Mopa\Bundle\BarcodeBundle\Model;

class BarcodeTypes{
    /**
     * Barcode Types
     * Zend_Barcode::factory will try to get the renderer, (numeric) 
     * If none is found local additional renderes will be tryed
     */
    protected static $types = array(
        1 => "codabar",
        2 => "code128",
        3 => "code25",
        4 => "code25interleaved",
        5 => "code39",
        6 => "ean13",
        7 => "ean2",
        8 => "ean5",
        9 => "ean8",
        10 => "identcode",
        11 => "itf14",
        12 => "leitcode",
        13 => "planet",
        14 => "postnet",
        15 => "royalmail",
        16 => "upca",
        17 => "upce",
        90 => "plainsequence",
        99 => "qr",
    );
    public static function getTypes(){
        return self::$types;
    }
}