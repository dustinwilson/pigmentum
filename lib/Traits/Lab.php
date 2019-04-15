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

        foreach ($xyz as &$m) {
            if ($m > Color::EPSILON) {
                $m = $m ** (1/3);
            } else {
                $m = (Color::KAPPA * $m + 16) / 116;
            }
        }

        $this->_Lab = new ColorSpaceLab((116 * $xyz[1]) - 16, 500 * ($xyz[0] - $xyz[1]), 200 * ($xyz[1] - $xyz[2]));

        return $this->_Lab;
    }

    public function toLch(): ColorSpaceLch {
        $c = sqrt($this->Lab->a * $this->_Lab->a + $this->_Lab->b * $this->_Lab->b);
        $h = fmod((rad2deg(atan2($this->_Lab->b, $this->_Lab->a)) + 360), 360);
        if (round($c * 10000) === 0) {
            $h = 0;
        }

        $this->_Lch = new ColorSpaceLch($this->_Lab->L, $c, $h);
        return $this->_Lch;
    }
}