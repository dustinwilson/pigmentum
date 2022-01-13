<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\RGB;

class HSB extends \dW\Pigmentum\ColorSpace\ColorSpace implements \Stringable {
    protected float $_H;
    protected float $_S;
    protected float $_B;


    public function __construct(float $H, float $S, float $B) {
        $this->_H = $H;
        $this->_S = $S;
        $this->_B = $B;
    }


    public function __toString(): string {
        return "hsb({$this->_H}, {$this->_S}, {$this->_B})";
    }
}