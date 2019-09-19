<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class Luv extends AbstractSpace {
    protected $_L;
    protected $_u;
    protected $_v;

    public function __construct(float $L, float $u, float $v) {
        $this->_L = $L;
        $this->_u = $u;
        $this->_v = $v;
    }
}