<?php

namespace PhpOffice\PhpWord;

/**
 * Simplified PhpWord class
 */
class PhpWord
{
    private $sections = array();
    private $properties;

    public function __construct()
    {
        $this->properties = new DocumentProperties();
    }

    public function addSection($sectionStyle = null)
    {
        $section = new Section();
        $this->sections[] = $section;
        return $section;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function getDocInfo()
    {
        return $this->properties;
    }
}

class Section
{
    private $elements = array();

    public function addText($text, $fontStyle = null, $paragraphStyle = null)
    {
        $this->elements[] = array(
            'type' => 'text',
            'content' => $text,
            'fontStyle' => $fontStyle,
            'paragraphStyle' => $paragraphStyle
        );
        return $this;
    }

    public function addTextBreak($count = 1, $fontStyle = null, $paragraphStyle = null)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->elements[] = array(
                'type' => 'textbreak'
            );
        }
        return $this;
    }

    public function getElements()
    {
        return $this->elements;
    }
}

class DocumentProperties
{
    private $creator = '';
    private $title = '';
    private $subject = '';
    private $description = '';

    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getDescription()
    {
        return $this->description;
    }
}