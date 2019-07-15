<?php
declare(strict_types=1);
namespace dW\Pigmentum\Traits;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\ColorSpace\Lab as ColorSpaceLab;
use dW\Pigmentum\ColorSpace\Lab\Lch as ColorSpaceLch;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

trait Lab {
    protected $_Lab;
    protected $_Lch;

    private static function _withLab(float $L, float $a, float $b, ColorSpaceLch $lch = null): Color {
        $L = min(max($L, 0), 100);
        $a = min(max($a, -128), 127);
        $b = min(max($b, -128), 127);

        $fy = ($L + 16.0) / 116.0;
        $fx = 0.002 * $a + $fy;
        $fz = $fy - 0.005 * $b;

        $fx3 = $fx ** 3;
        $fz3 = $fz ** 3;

        $xr = ($fx3 > Color::EPSILON) ? $fx3 : (116.0 * $fx - 16.0) / Color::KAPPA;
        $yr = ($L > Color::KAPPA * Color::EPSILON) ? (($L + 16.0) / 116.0) ** 3 : $L / self::KAPPA;
        $zr = ($fz3 > Color::EPSILON) ? $fz3 : (116.0 * $fz - 16.0) / Color::KAPPA;

        return new self($xr * Color::ILLUMINANT_D50[0], $yr * Color::ILLUMINANT_D50[1], $zr * Color::ILLUMINANT_D50[2], [
            'Lab' => new ColorSpaceLab($L, $a, $b),
            'Lch' => $lch
        ]);
    }

    public static function withLab(float $L, float $a, float $b): Color {
        return self::_withLab($L, $a, $b);
    }

    public static function withLch(float $L, float $c, float $h): Color {
        $hh = deg2rad($h);
        return self::withLab($L, cos($hh) * $c, sin($hh) * $c, new ColorSpaceLch($L, $c, $h));
    }

    public function toLab(): ColorSpaceLab {
        if (!is_null($this->_Lab)) {
            return $this->_Lab;
        }

        $xyz = $this->_XYZ;

        $xyz = [
            $xyz->x / Color::ILLUMINANT_D50[0],
            $xyz->y / Color::ILLUMINANT_D50[1],
            $xyz->z / Color::ILLUMINANT_D50[2]
        ];

        $xyz = array_map(function($n) {
            if ($n > Color::EPSILON) {
                return $n ** (1/3);
            } else {
                return (Color::KAPPA * $n + 16.0) / 116.0;
            }
        }, $xyz);

        // Catch an edge case where a value might be an infantesimally small negative number, causing hell in math later on if needing to convert to Lch at the cost of a few decimal points. Also get rid of garbage values like "-0"...
        $L = round(116 * $xyz[1] - 16, 5);
        $L = ($L != 0) ? $L : 0;
        $a = round(500 * ($xyz[0] - $xyz[1]), 5);
        $a = ($a != 0) ? $a : 0;
        $b = round(200 * ($xyz[1] - $xyz[2]), 5);
        $b = ($b != 0) ? $b : 0;

        $this->_Lab = new ColorSpaceLab($L, $a, $b);

        return $this->_Lab;
    }

    public function toLch(): ColorSpaceLch {
        if (is_null($this->_Lab)) {
            $this->toLab();
        }

        $c = sqrt($this->_Lab->a**2 + $this->_Lab->b**2);

        $h = rad2deg(atan2($this->_Lab->b, $this->_Lab->a));
        if ($h < 0) {
            $h += 360;
        }

        $this->_Lch = new ColorSpaceLch($this->_Lab->L, $c, $h);
        return $this->_Lch;
    }
}