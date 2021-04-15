<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\ColorSpace\Luv\LCHuv as LCHuv;

class Luv extends ColorSpace {
    protected $_L;
    protected $_u;
    protected $_v;

    // Child color spaces
    protected $_LCHuv;


    public function __construct(float $L, float $u, float $v, ?LCHuv $LCHuv = null) {
        $this->_L = $L;
        $this->_u = $u;
        $this->_v = $v;

        if ($LCHuv !== null) {
            $this->_LCHuv = $LCHuv;
        }
    }

    public function toLCHuv(): LCHuv {
        $c = sqrt($this->_u ** 2 + $this->_v ** 2);

        $h = rad2deg(atan2($this->_v, $this->_u));
        if ($h < 0) {
            $h += 360;
        }

        $this->_LCHuv = new LCHuv($this->_L, $c, $h);
        return $this->_LCHuv;
    }
}