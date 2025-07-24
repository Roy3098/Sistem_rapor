<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Simplified XLSX Writer
 */
class Xlsx
{
    private $spreadsheet;

    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
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
        $sheet = $this->spreadsheet->getActiveSheet();
        $cells = $sheet->getCells();
        
        // Create CSV content for Excel compatibility
        $csvContent = '';
        $maxRow = 0;
        $maxCol = 0;
        
        // Find max dimensions
        foreach ($cells as $coordinate => $value) {
            preg_match('/([A-Z]+)(\d+)/', $coordinate, $matches);
            $col = ord($matches[1]) - 64;
            $row = (int)$matches[2];
            $maxRow = max($maxRow, $row);
            $maxCol = max($maxCol, $col);
        }
        
        // Generate CSV rows
        for ($row = 1; $row <= $maxRow; $row++) {
            $rowData = array();
            for ($col = 1; $col <= $maxCol; $col++) {
                $coordinate = chr(64 + $col) . $row;
                $value = isset($cells[$coordinate]) ? $cells[$coordinate] : '';
                $rowData[] = '"' . str_replace('"', '""', $value) . '"';
            }
            $csvContent .= implode(',', $rowData) . "\n";
        }
        
        echo $csvContent;
    }

    private function saveToFile($filename)
    {
        // For file saving, create a simple Excel-compatible format
        $content = $this->generateExcelContent();
        file_put_contents($filename, $content);
    }

    private function generateExcelContent()
    {
        // Generate basic Excel XML structure
        $sheet = $this->spreadsheet->getActiveSheet();
        $cells = $sheet->getCells();
        
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        $xml .= '<sheetData>' . "\n";
        
        $rows = array();
        foreach ($cells as $coordinate => $value) {
            preg_match('/([A-Z]+)(\d+)/', $coordinate, $matches);
            $row = (int)$matches[2];
            if (!isset($rows[$row])) {
                $rows[$row] = array();
            }
            $rows[$row][$coordinate] = $value;
        }
        
        foreach ($rows as $rowNum => $rowCells) {
            $xml .= '<row r="' . $rowNum . '">' . "\n";
            foreach ($rowCells as $coordinate => $value) {
                $xml .= '<c r="' . $coordinate . '" t="inlineStr">';
                $xml .= '<is><t>' . htmlspecialchars($value) . '</t></is>';
                $xml .= '</c>' . "\n";
            }
            $xml .= '</row>' . "\n";
        }
        
        $xml .= '</sheetData>' . "\n";
        $xml .= '</worksheet>';
        
        return $xml;
    }
}