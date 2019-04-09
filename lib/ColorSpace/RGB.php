<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class RGB extends AbstractSpace {
    protected $_r;
    protected $_g;
    protected $_b;

    protected $_hex;
    protected $_workingSpace;

    public function __construct(float $r, float $g, float $b, string $workingSpace = dW\Pigmentum\Color::WORKING_SPACE_RGB_sRGB, $hex = null) {
        $this->_r = $r;
        $this->_g = $g;
        $this->_b = $b;
        $this->_workingSpace = $workingSpace;
        $this->_hex = $hex;
    }

    public function toHex(): string {
        if (!is_null($this->_hex)) {
            return $this->_hex;
        }

        $this->_hex = sprintf("#%02x%02x%02x", (int)round($this->_r), (int)round($this->_g), (int)round($this->_b));
        return $this->_hex;
    }
}