<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\ColorSpace\Lab\LCHab as LCHab;

class Lab extends ColorSpace {
    protected $_L;
    protected $_a;
    protected $_b;

    // Child color spaces
    protected $_LCHab;


    public function __construct(float $L, float $a, float $b, ?LCHab $LCHab = null) {
        $this->_L = $L;
        $this->_a = $a;
        $this->_b = $b;

        if ($LCHab !== null) {
            $this->_LCHab = $LCHab;
        }
    }

    public function toLCHab(): LCHab {
        $c = sqrt($this->_a**2 + $this->_b**2);

        $h = rad2deg(atan2($this->_b, $this->_a));
        if ($h < 0) {
            $h += 360;
        }

        $this->_LCHab = new LCHab($this->_L, $c, $h);
        return $this->_LCHab;
    }

    public function __toString() {
        return "lab({$this->_L}, {$this->_a}, {$this->_b})";
    }
}