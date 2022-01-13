<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\ColorSpace\Lab\LCHab as ColorSpaceLCHab;

class Lab extends ColorSpace implements \Stringable {
    protected float $_L;
    protected float $_a;
    protected float $_b;

    // Child color spaces
    protected ?ColorSpaceLCHab $_LCHab = null;


    public function __construct(float $L, float $a, float $b, ?ColorSpaceLCHab $LCHab = null) {
        $this->_L = $L;
        $this->_a = $a;
        $this->_b = $b;

        if ($LCHab !== null) {
            $this->_LCHab = $LCHab;
        }
    }


    public function toLCHab(): ColorSpaceLCHab {
        $c = sqrt($this->_a**2 + $this->_b**2);

        $h = rad2deg(atan2($this->_b, $this->_a));
        if ($h < 0) {
            $h += 360;
        }

        $this->_LCHab = new ColorSpaceLCHab($this->_L, $c, $h);
        return $this->_LCHab;
    }


    public function __toString(): string {
        return "lab({$this->_L}, {$this->_a}, {$this->_b})";
    }
}