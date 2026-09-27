<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class Border
{
    const BORDER_THIN = 'thin';
    const BORDER_THICK = 'thick';
    const BORDER_MEDIUM = 'medium';

    private $borderStyle;

    public function setBorderStyle($style)
    {
        $this->borderStyle = $style;
        return $this;
    }

    public function getBorderStyle()
    {
        return $this->borderStyle;
    }
}

class Borders
{
    private $allBorders;

    public function __construct()
    {
        $this->allBorders = new Border();
    }

    public function getAllBorders()
    {
        return $this->allBorders;
    }
}