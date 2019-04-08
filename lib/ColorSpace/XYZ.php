<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class XYZ extends AbstractSpace {
    protected $_x;
    protected $_y;
    protected $_z;

    public function __construct(float $x, float $y, float $z) {
        $this->_x = $x;
        $this->_y = $y;
        $this->_z = $z;
    }
}