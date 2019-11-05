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

    private static function _withLab(float $L, float $a, float $b, ?string $name = null, ?ColorSpaceLCHab $LCHab = null): Color {
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

        return new self($xr * Color::ILLUMINANT_D50[0], $yr * Color::ILLUMINANT_D50[1], $zr * Color::ILLUMINANT_D50[2], $name, [
            'Lab' => new ColorSpaceLab($L, $a, $b),
            'LCHab' => $LCHab
        ]);
    }

    public static function withLab(float $L, float $a, float $b, ?string $name = null): Color {
        return self::_withLab($L, $a, $b, $name);
    }

    public static function withLCHab(float $L, float $C, float $H, ?string $name = null): Color {
        $hh = deg2rad($H);
        return self::_withLab($L, cos($hh) * $C, sin($hh) * $C, $name, new ColorSpaceLCHab($L, $C, $H));
    }

    public function toLab(): ColorSpaceLab {
        if (!is_null($this->_Lab)) {
            return $this->_Lab;
        }

        $xyz = $this->_XYZ;

        $xyz = [
            $xyz->X / Color::ILLUMINANT_D50[0],
            $xyz->Y / Color::ILLUMINANT_D50[1],
            $xyz->Z / Color::ILLUMINANT_D50[2]
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

    public static function averageWithLab(Color ...$colors): Color {
        $aSum = 0;
        $bSum = 0;
        $cSum = 0;
        $length = sizeof($colors);

        foreach ($colors as $c) {
            $aSum += $c->Lab->L;
            $bSum += $c->Lab->a;
            $cSum += $c->Lab->b;
        }

        return Color::withLab($aSum / $length, $bSum / $length, $cSum / $length);
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

    public static function averageWithLCHab(Color ...$colors): Color {
        $aSum = 0;
        $bSum = 0;
        $cSum = 0;
        $length = sizeof($colors);

        foreach ($colors as $c) {
            $aSum += $c->LCHab->L;
            $bSum += $c->LCHab->C;
            $cSum += $c->LCHab->H;
        }

        return Color::withLCHab($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithLCHab(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        $aL = $this->LCHab->L;
        $aC = $this->LCHab->C;
        $aH = $this->LCHab->H;
        $bL = $color->LCHab->L;
        $bC = $color->LCHab->C;
        $bH = $color->LCHab->H;

        // If the chroma is 0 then the hue doesn't matter. The color is grey,
        // so to keep mixing from going across the entire hue range in some
        // cases...
        if ($aC == 0) {
            $aH = $bH;
        } elseif ($bC == 0) {
            $bH = $aH;
        }
        // Hue is a circle mathematically represented in 360 degrees from 0 to
        // 359. This means that the shortest distance isn't always positive and
        // sometimes going backwards is the correct way to mix.
        elseif (abs($bH - $aH) > 180) {
            if ($aH > $bH) {
                $bH += 360;
            } else {
                $aH += 360;
            }
        }

        $H = $aH + ($percentage * ($bH - $aH));
        $H = ($H > 359) ? $H - 360 : $H;

        return Color::withLCHab(
            $aL + ($percentage * ($bL - $aL)),
            $aC + ($percentage * ($bC - $aC)),
            $H
        );
    }
}
