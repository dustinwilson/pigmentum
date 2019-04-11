<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\Lab;

class LCH extends \dW\Pigmentum\ColorSpace\AbstractSpace {
    protected $_L;
    protected $_c;
    protected $_h;

    public function __construct(float $L, float $c, float $h) {
        $this->_L = $L;
        $this->_c = $c;
        $this->_h = $h;
    }
}