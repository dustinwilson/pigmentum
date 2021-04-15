<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\ColorSpace\Lab as ColorSpaceLab;
use dW\Pigmentum\ColorSpace\Lab\LCHab as ColorSpaceLCHab;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

trait Lab {
    protected $_Lab;

    private static function _withLab(float $L, float $a, float $b, ?string $name = null, ?ColorSpaceLCHab $LCHab = null): Color {
        $L = min(max($L, 0), 100);
        $a = min(max($a, -128), 127);
        $b = min(max($b, -128), 127);

        $fy = ($L + 16.0) / 116.0;
        $fx = 0.002 * $a + $fy;
        $fz = $fy - 0.005 * $b;

        $fx3 = $fx ** 3;
        $fz3 = $fz ** 3;

        $xr = ($fx3 > self::EPSILON) ? $fx3 : (116.0 * $fx - 16.0) / self::KAPPA;
        $yr = ($L > self::KAPPA * self::EPSILON) ? (($L + 16.0) / 116.0) ** 3 : $L / self::KAPPA;
        $zr = ($fz3 > self::EPSILON) ? $fz3 : (116.0 * $fz - 16.0) / self::KAPPA;

        return new self($xr * self::REFERENCE_WHITE[0], $yr * self::REFERENCE_WHITE[1], $zr * self::REFERENCE_WHITE[2], $name, [ 'Lab' => new ColorSpaceLab($L, $a, $b, $LCHab) ]);
    }

    public static function withLab(float $L, float $a, float $b, ?string $name = null): Color {
        return self::_withLab($L, $a, $b, $name);
    }

    public static function withLCHab(float $L, float $C, float $H, ?string $name = null): Color {
        $hh = deg2rad($H);
        return self::_withLab($L, cos($hh) * $C, sin($hh) * $C, $name, new ColorSpaceLCHab($L, $C, $H));
    }

    public function toLab(): ColorSpaceLab {
        if ($this->_Lab !== null) {
            return $this->_Lab;
        }

        $xyz = $this->_XYZ;

        $xr = $this->_XYZ / self::REFERENCE_WHITE[0];

        $xyz = [
            $xyz->X / self::REFERENCE_WHITE[0],
            $xyz->Y / self::REFERENCE_WHITE[1],
            $xyz->Z / self::REFERENCE_WHITE[2]
        ];

        $xyz = array_map(function($n) {
            return ($n > self::EPSILON) ? $n ** (1/3) : (self::KAPPA * $n + 16) / 116;
        }, $xyz);

        $L = 116 * $xyz[1] - 16;
        $a = 500 * ($xyz[0] - $xyz[1]);
        $b = 200 * ($xyz[1] - $xyz[2]);

        // Combat issues where -0 would interfere in math down the road.
        $this->_Lab = new ColorSpaceLab(($L == -0) ? 0 : $L, ($a == -0) ? 0 : $a, ($b == -0) ? 0 : $b);
        return $this->_Lab;
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

        return self::withLab($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithLab(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        return self::withLab(
            $this->Lab->L + ($percentage * ($color->Lab->L - $this->Lab->L)),
            $this->Lab->a + ($percentage * ($color->Lab->a - $this->Lab->a)),
            $this->Lab->b + ($percentage * ($color->Lab->b - $this->Lab->b))
        );
    }

    // Mix with L*a*b* by default. Colors in this color space are perceptively
    // uniform and are perfect for mixing.
    public function mix(Color $color, float $percentage = 0.5): Color {
        return self::mixWithLab($color, $percentage);
    }

    public static function averageWithLCHab(Color ...$colors): Color {
        $aSum = 0;
        $bSum = 0;
        $cSum = 0;
        $length = sizeof($colors);

        foreach ($colors as $c) {
            $aSum += $c->Lab->LCHab->L;
            $bSum += $c->Lab->LCHab->C;
            $cSum += $c->Lab->LCHab->H;
        }

        return self::withLCHab($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithLCHab(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        $aL = $this->Lab->LCHab->L;
        $aC = $this->Lab->LCHab->C;
        $aH = $this->Lab->LCHab->H;
        $bL = $color->Lab->LCHab->L;
        $bC = $color->Lab->LCHab->C;
        $bH = $color->Lab->LCHab->H;

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

        return self::withLCHab(
            $aL + ($percentage * ($bL - $aL)),
            $aC + ($percentage * ($bC - $aC)),
            $H
        );
    }
}
