<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\Lab;
use \dW\Pigmentum\Color as Color;

class LCHab extends \dW\Pigmentum\ColorSpace\ColorSpace {
    protected $_L;
    protected $_C;
    protected $_H;

    public function __construct(float $L, float $C, float $H) {
        $this->_L = $L;
        $this->_C = $C;
        $this->_H = $H;
    }

    public function __toString() {
        $className = strtolower(self::class);
        return "$className({$this->_L}, {$this->_C}, {$this->_H})";
    }
}