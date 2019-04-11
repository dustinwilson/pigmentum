<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class Lab extends AbstractSpace {
    protected $_L;
    protected $_a;
    protected $_b;

    protected $_Lch;

    public function __construct(float $L, float $a, float $b, Lab\LCH $Lch = null) {
        $this->_L = $L;
        $this->_a = $a;
        $this->_b = $b;
        $this->_Lch = $Lch;
    }

    public function toLCH(): Lab\LCH {
        $c = sqrt($this->_a * $this->_a + $this->_b * $this->_b);
        $h = fmod((rad2deg(atan2($this->_b, $this->_a)) + 360), 360);
        if (round($c * 10000) === 0) {
            $h = 0;
        }

        $this->_Lch = new Lab\LCH($this->_L, $c, $h);
        return $this->_Lch;
    }
}