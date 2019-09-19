<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\Lab;

class LCHab extends \dW\Pigmentum\ColorSpace\AbstractSpace {
    protected $_L;
    protected $_C;
    protected $_H;

    public function __construct(float $L, float $C, float $H) {
        $this->_L = $L;
        $this->_C = $C;
        $this->_H = $H;
    }
}