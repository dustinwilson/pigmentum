<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\Color as Color;

class RGB extends ColorSpace {
    protected $_R;
    protected $_G;
    protected $_B;

    protected $_workingSpace;

    public function __construct(float $R, float $G, float $B, string $workingSpace = null) {
        if (is_null($workingSpace)) {
            $workingSpace = Color::$workingSpace;
        }

        $this->_R = $R;
        $this->_G = $G;
        $this->_B = $B;
        $this->_workingSpace = $workingSpace;
    }
}