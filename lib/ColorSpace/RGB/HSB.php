<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\RGB;

class HSB extends \dW\Pigmentum\ColorSpace\AbstractSpace {
    protected $_h;
    protected $_s;
    protected $_b;

    public function __construct(float $h, float $s, float $b) {
        $this->_h = $h;
        $this->_s = $s;
        $this->_b = $b;
    }
}