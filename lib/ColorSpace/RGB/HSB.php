<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\RGB;

class HSB extends \dW\Pigmentum\ColorSpace\AbstractSpace {
    protected $_H;
    protected $_S;
    protected $_B;

    public function __construct(float $H, float $S, float $B) {
        $this->_H = $H;
        $this->_S = $S;
        $this->_B = $B;
    }
}