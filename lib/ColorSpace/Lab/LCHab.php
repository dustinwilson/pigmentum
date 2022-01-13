<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\Lab;
use \dW\Pigmentum\Color as Color;

class LCHab extends \dW\Pigmentum\ColorSpace\ColorSpace implements \Stringable {
    protected float $_L;
    protected float $_C;
    protected float $_H;


    public function __construct(float $L, float $C, float $H) {
        $this->_L = $L;
        $this->_C = $C;
        $this->_H = $H;
    }


    public function __toString(): string {
        return "lchab({$this->_L}, {$this->_C}, {$this->_H})";
    }
}