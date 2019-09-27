<?php
declare(strict_types=1);
namespace dW\Pigmentum\Traits;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\ColorSpace\Lab as ColorSpaceLab;
use dW\Pigmentum\ColorSpace\Lab\LCHab as ColorSpaceLCHab;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

trait Lab {
    protected $_Lab;
    protected $_LCHab;

    private static function _withLab(float $L, float $a, float $b, ColorSpaceLCHab $LCHab = null): Color {
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
            'LCHab' => $LCHab
        ]);
    }

    public static function withLab(float $L, float $a, float $b): Color {
        return self::_withLab($L, $a, $b);
    }

    public static function withLCHab(float $L, float $C, float $H): Color {
        $hh = deg2rad($H);
        return self::_withLab($L, cos($hh) * $C, sin($hh) * $C, new ColorSpaceLCHab($L, $C, $H));
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

        $L = round(116 * $xyz[1] - 16, 5);
        $a = round(500 * ($xyz[0] - $xyz[1]), 5);
        $b = round(200 * ($xyz[1] - $xyz[2]), 5);

        // Combat issues where -0 would interfere in math down the road.
        $this->_Lab = new ColorSpaceLab(($L == -0) ? 0 : $L, ($a == -0) ? 0 : $a, ($b == -0) ? 0 : $b);
        return $this->_Lab;
    }

    public function toLCHab(): ColorSpaceLCHab {
        if (is_null($this->_Lab)) {
            $this->toLab();
        }

        $c = sqrt($this->_Lab->a**2 + $this->_Lab->b**2);

        $h = rad2deg(atan2($this->_Lab->b, $this->_Lab->a));
        if ($h < 0) {
            $h += 360;
        }

        $this->_LCHab = new ColorSpaceLCHab($this->_Lab->L, $c, $h);
        return $this->_LCHab;
    }


    public function mixWithLab(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        return Color::withLab(
            $this->Lab->L + ($percentage * ($color->Lab->L - $this->Lab->L)),
            $this->Lab->a + ($percentage * ($color->Lab->a - $this->Lab->a)),
            $this->Lab->b + ($percentage * ($color->Lab->b - $this->Lab->b))
        );
    }

    // Mix with L*a*b* by default. Colors in this color space are perceptively
    // uniform and are perfect for mixing.
    public function mix(Color $color, float $percentage = 0.5): Color {
        return Color::mixWithLab($color, $percentage);
    }

    public function mixWithLCHab(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        return Color::withLCHab(
            $this->LCHab->L + ($percentage * ($color->LCHab->L - $this->LCHab->L)),
            $this->LCHab->C + ($percentage * ($color->LCHab->C - $this->LCHab->C)),
            $this->LCHab->H + ($percentage * ($color->LCHab->H - $this->LCHab->H))
        );
    }
}
