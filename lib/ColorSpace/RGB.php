<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class RGB extends AbstractSpace {
    protected $_r;
    protected $_g;
    protected $_b;

    protected $_hex;
    protected $_hsb;
    protected $_workingSpace;

    public function __construct(float $r, float $g, float $b, string $workingSpace = dW\Pigmentum\Color::WORKING_SPACE_RGB_sRGB, string $hex = null, RGB\HSB $hsb = null) {
        $this->_r = $r;
        $this->_g = $g;
        $this->_b = $b;
        $this->_workingSpace = $workingSpace;
        $this->_hex = $hex;
        $this->_hsb = $hsb;
    }

    public function toHex(): string {
        if (!is_null($this->_hex)) {
            return $this->_hex;
        }

        $this->_hex = sprintf("#%02x%02x%02x", (int)round($this->_r), (int)round($this->_g), (int)round($this->_b));
        return $this->_hex;
    }

    public function toHSB(): RGB\HSB {
        if (!is_null($this->_hsb)) {
            return $this->_hsb;
        }

        $r = $this->_r / 255;
        $g = $this->_g / 255;
        $b = $this->_b / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $d = $max - $min;
        $v = $max;

        if ($d == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $d / $max;

            $R = ((($max - $r) / 6) + ($d / 2)) / $d;
            $G = ((($max - $g) / 6) + ($d / 2)) / $d;
            $B = ((($max - $b) / 6) + ($d / 2)) / $d;

            if ($r == $max) {
                $h = $B - $G;
            } elseif ($g == $max) {
                $h = (1 / 3) + $R - $B;
            } elseif ($b == $max) {
                $h = (2 / 3) + $G - $R;
            }

            if ($h < 0) {
                $h += 1;
            }
            if ($h > 1) {
                $h -= 1;
            }
        }

        $this->_hsb = new RGB\HSB($h, $s, $v);
        return $this->_hsb;
    }
}