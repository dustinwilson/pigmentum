<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\Lab;
use \dW\Pigmentum\Color as Color;

class LCHab extends \dW\Pigmentum\ColorSpace\AbstractSpace {
    protected $_L;
    protected $_C;
    protected $_H;

    public function __construct(float $L, float $C, float $H) {
        $this->_L = $L;
        $this->_C = $C;
        $this->_H = $H;
    }

    // Chroma in LCH is variable based upon the hue and lightness of the color;
    // this will return the maximum possible chroma for the color that is
    // possible within a supplied RGB working space.
    public function getMaximumChroma(string $rgbWorkingSpace = null): float {
        if (is_null($rgbWorkingSpace)) {
            $rgbWorkingSpace = Color::$workingSpace;
        }

        $hh = deg2rad($this->_H);
        $min = INF;

        // Find the boundaries in the form of slope/intercepts and get the shortest one.
        $sub = ($this->_L + 16) ** 3 / 1560896;
        $sub = ($sub > Color::EPSILON) ? $sub : $this->_L / Color::KAPPA;
        $matrix = $rgbWorkingSpace::getXYZMatrix()->inverse();

        for ($i = 0; $i < $matrix->getM(); $i++) {
            $row = $matrix[$i];

            for ($j = 0; $j <= 1; $j++) {
                $top1 = (284517 * $row[0] - 94839 * $row[2]) * $sub;
                $top2 = (838422 * $row[2] + 769860 * $row[1] + 731718 * $row[0]) * $this->_L * $sub - 769860 * $j * $this->_L;
                $bottom = (632260 * $row[2] - 126452 * $row[1]) * $sub + 126452 * $j;

                $slope = $top1 / $bottom;
                $intercept = $top2 / $bottom;
                $length = $intercept / (sin($hh) - $slope * cos($hh));
                if ($length >= 0) {
                    $min = min($min, $length);
                }
            }
        }

        return $min;
    }
}