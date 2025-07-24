<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class Alignment
{
    const HORIZONTAL_CENTER = 'center';
    const HORIZONTAL_LEFT = 'left';
    const HORIZONTAL_RIGHT = 'right';

    private $horizontal;

    public function setHorizontal($horizontal)
    {
        $this->horizontal = $horizontal;
        return $this;
    }

    public function getHorizontal()
    {
        return $this->horizontal;
    }
}