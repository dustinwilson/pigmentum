<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\Luv;

class HSLuv extends \dW\Pigmentum\ColorSpace\AbstractSpace {
    protected $_H;
    protected $_S;
    protected $_L;

    public function __construct(float $H, float $S, float $L) {
        $this->_H = $H;
        $this->_S = $S;
        $this->_L = $L;
    }
}