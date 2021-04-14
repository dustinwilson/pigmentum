<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class Lab extends ColorSpace {
    protected $_L;
    protected $_a;
    protected $_b;

    public function __construct(float $L, float $a, float $b) {
        $this->_L = $L;
        $this->_a = $a;
        $this->_b = $b;
    }
}