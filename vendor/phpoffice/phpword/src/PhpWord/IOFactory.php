<?php

namespace PhpOffice\PhpWord;

/**
 * Simplified IOFactory for PhpWord
 */
class IOFactory
{
    public static function createWriter(PhpWord $phpWord, $format = 'Word2007')
    {
        return new Writer\Word2007($phpWord);
    }
}

namespace PhpOffice\PhpWord\Writer;

use PhpOffice\PhpWord\PhpWord;

class Word2007
{
    private $phpWord;

    public function __construct(PhpWord $phpWord)
    {
        $this->phpWord = $phpWord;
    }

    public function save($filename)
    {
        if ($filename === 'php://output') {
            $this->outputToStream();
        } else {
            $this->saveToFile($filename);
        }
    }

    private function outputToStream()
    {
        $content = $this->generateWordContent();
        
        // Set headers for Word document download
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Length: ' . strlen($content));
        
        echo $content;
    }

    private function saveToFile($filename)
    {
        $content = $this->generateWordContent();
        file_put_contents($filename, $content);
    }

    private function generateWordContent()
    {
        $sections = $this->phpWord->getSections();
        $content = '';
        
        // Generate RTF content for better compatibility
        $content .= "{\rtf1\ansi\deff0 {\fonttbl {\f0 Times New Roman;}}";
        
        foreach ($sections as $section) {
            $elements = $section->getElements();
            foreach ($elements as $element) {
                if ($element['type'] === 'text') {
                    $text = $element['content'];
                    $fontStyle = $element['fontStyle'];
                    
                    if (is_array($fontStyle)) {
                        if (isset($fontStyle['bold']) && $fontStyle['bold']) {
                            $content .= '\b ';
                        }
                        if (isset($fontStyle['size'])) {
                            $content .= '\fs' . ($fontStyle['size'] * 2) . ' ';
                        }
                    }
                    
                    $content .= $text . '\par ';
                    
                    if (is_array($fontStyle) && isset($fontStyle['bold']) && $fontStyle['bold']) {
                        $content .= '\b0 ';
                    }
                } elseif ($element['type'] === 'textbreak') {
                    $content .= '\par ';
                }
            }
        }
        
        $content .= '}';
        
        return $content;
    }
}