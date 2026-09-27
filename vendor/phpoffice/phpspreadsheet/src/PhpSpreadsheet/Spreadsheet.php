<?php

namespace PhpOffice\PhpSpreadsheet;

/**
 * Simplified PhpSpreadsheet class for basic functionality
 */
class Spreadsheet
{
    private $activeSheet;
    private $properties;

    public function __construct()
    {
        $this->activeSheet = new Worksheet();
        $this->properties = new DocumentProperties();
    }

    public function getActiveSheet()
    {
        return $this->activeSheet;
    }

    public function getProperties()
    {
        return $this->properties;
    }
}

class Worksheet
{
    private $cells = array();
    private $columnDimensions = array();
    private $styles = array();

    public function setCellValue($coordinate, $value)
    {
        $this->cells[$coordinate] = $value;
        return $this;
    }

    public function setCellValueByColumnAndRow($column, $row, $value)
    {
        $coordinate = $this->getCoordinateFromColumnAndRow($column, $row);
        return $this->setCellValue($coordinate, $value);
    }

    public function getColumnDimension($column)
    {
        if (!isset($this->columnDimensions[$column])) {
            $this->columnDimensions[$column] = new ColumnDimension();
        }
        return $this->columnDimensions[$column];
    }

    public function getStyle($range)
    {
        if (!isset($this->styles[$range])) {
            $this->styles[$range] = new Style();
        }
        return $this->styles[$range];
    }

    private function getCoordinateFromColumnAndRow($column, $row)
    {
        return chr(64 + $column) . $row;
    }

    public function getCells()
    {
        return $this->cells;
    }

    public function getStyles()
    {
        return $this->styles;
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
}

class ColumnDimension
{
    public function setAutoSize($autoSize)
    {
        return $this;
    }
}

class Style
{
    private $font;
    private $fill;
    private $alignment;
    private $borders;

    public function __construct()
    {
        $this->font = new Style\Font();
        $this->fill = new Style\Fill();
        $this->alignment = new Style\Alignment();
        $this->borders = new Style\Borders();
    }

    public function applyFromArray($styleArray)
    {
        return $this;
    }

    public function getFont()
    {
        return $this->font;
    }

    public function getFill()
    {
        return $this->fill;
    }

    public function getAlignment()
    {
        return $this->alignment;
    }

    public function getBorders()
    {
        return $this->borders;
    }
}