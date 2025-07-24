<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class Fill
{
    const FILL_SOLID = 'solid';

    private $fillType;
    private $startColor;

    public function setFillType($fillType)
    {
        $this->fillType = $fillType;
        return $this;
    }

    public function getStartColor()
    {
        if (!$this->startColor) {
            $this->startColor = new Color();
        }
        return $this->startColor;
    }
}

class Color
{
    private $rgb;

    public function setRGB($rgb)
    {
        $this->rgb = $rgb;
        return $this;
    }

    public function getRGB()
    {
        return $this->rgb;
    }
}

// Add Font class for completeness
namespace PhpOffice\PhpSpreadsheet\Style;

class Font
{
    private $bold = false;
    private $color;

    public function setBold($bold)
    {
        $this->bold = $bold;
        return $this;
    }

    public function getBold()
    {
        return $this->bold;
    }

    public function getColor()
    {
        if (!$this->color) {
            $this->color = new Color();
        }
        return $this->color;
    }
}