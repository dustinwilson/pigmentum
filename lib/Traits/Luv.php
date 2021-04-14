<?php
declare(strict_types=1);
namespace dW\Pigmentum\Traits;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\ColorSpace\Luv as ColorSpaceLuv;
use dW\Pigmentum\ColorSpace\Luv\LCHuv as ColorSpaceLCHuv;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

trait Luv {
    protected $_Luv;
    protected $_LCHuv;

    private static function _withLuv(float $L, float $u, float $v, ?string $name = null, ?ColorSpaceLCHuv $LCHuv = null): Color {
        $u0 = (4 * self::REFERENCE_WHITE[0]) / (self::REFERENCE_WHITE[0] + 15 * self::REFERENCE_WHITE[1] + 3 * self::REFERENCE_WHITE[2]);
        $v0 = (9 * self::REFERENCE_WHITE[0]) / (self::REFERENCE_WHITE[0] + 15 * self::REFERENCE_WHITE[1] + 3 * self::REFERENCE_WHITE[2]);

        $Y = ($L > self::KAPPA * self::EPSILON) ? (($L + 16.0) / 116.0) ** 3 : $L / self::KAPPA;

        $a = (1 / 3) * ((52 * $L / ($u + 13 * $L * $u0)) - 1);
        $b = -5 * $Y;
        $c = 0 - (1 / 3);
        $d = $Y * ((39 * $L / $v + 13 * $L * $u0) - 5);

        $X = ($d - $b) / ($a - $c);
        $Z = $X * ($a + $b);

        return new self($X, $Y, $Z, $name, [
            'Luv' => new ColorSpaceLuv($L, $u, $v),
            'LCHuv' => $LCHuv
        ]);
    }

    public static function withLuv(float $L, float $u, float $v, ?string $name = null): Color {
        return self::_withLuv($L, $u, $v, $name);
    }

    private static function withLCHuv(float $L, float $C, float $H, ?string $name = null): Color {
        $hh = deg2rad($H);
        return self::_withLuv($L, cos($hh) * $C, sin($hh) * $C, $name, new ColorSpaceLCHuv($L, $C, $H));
    }

    public function toLuv(): ColorSpaceLuv {
        if (!is_null($this->_Luv)) {
            return $this->_Luv;
        }

        $xyz = $this->_XYZ;

        $yr = $xyz->Y / self::REFERENCE_WHITE[1];
        $uPrime = (4 * $xyz->X) / ($xyz->X + 15 * $xyz->Y + 3 * $xyz->Z);
        $vPrime = (9 * $xyz->Y) / ($xyz->X + 15 * $xyz->Y + 3 * $xyz->Z);
        $uPrimeR = (4 * self::REFERENCE_WHITE[0]) / (self::REFERENCE_WHITE[0] + 15 * self::REFERENCE_WHITE[1] + 3 * self::REFERENCE_WHITE[2]);
        $vPrimeR = (9 * self::REFERENCE_WHITE[1]) / (self::REFERENCE_WHITE[0] + 15 * self::REFERENCE_WHITE[1] + 3 * self::REFERENCE_WHITE[2]);

        $L = ($yr > self::EPSILON) ? 116 * ($yr ** (1 / 3)) - 16 : self::KAPPA * $yr;
        $u = round(13 * $L * ($uPrime - $uPrimeR), 5);
        $v = round(13 * $L * ($vPrime - $vPrimeR), 5);
        $L = round($L, 5);

        // Combat issues where -0 would interfere in math down the road.
        $this->_Luv = new ColorSpaceLuv(($L == -0) ? 0 : $L, ($u == -0) ? 0 : $u, ($v == -0) ? 0 : $v);
        return $this->_Luv;
    }

    public function toLCHuv(): ColorSpaceLCHuv {
        if (is_null($this->_Luv)) {
            $this->toLuv();
        }

        $c = sqrt($this->_Luv->u ** 2 + $this->_Luv->v ** 2);

        $h = rad2deg(atan2($this->_Luv->v, $this->_Luv->u));
        if ($h < 0) {
            $h += 360;
        }

        $this->_LCHuv = new ColorSpaceLCHuv($this->_Luv->L, $c, $h);
        return $this->_LCHuv;
    }


    public static function averageWithLuv(Color ...$colors): Color {
        $aSum = 0;
        $bSum = 0;
        $cSum = 0;
        $length = sizeof($colors);

        foreach ($colors as $c) {
            $aSum += $c->Luv->L;
            $bSum += $c->Luv->u;
            $cSum += $c->Luv->v;
        }

        return self::withLuv($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithLuv(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        return self::withLuv(
            $this->Luv->L + ($percentage * ($color->Luv->L - $this->Luv->L)),
            $this->Luv->u + ($percentage * ($color->Luv->u - $this->Luv->u)),
            $this->Luv->v + ($percentage * ($color->Luv->v - $this->Luv->v))
        );
    }

    public static function averageWithLCHuv(Color ...$colors): Color {
        $aSum = 0;
        $bSum = 0;
        $cSum = 0;
        $length = sizeof($colors);

        foreach ($colors as $c) {
            $aSum += $c->LCHuv->L;
            $bSum += $c->LCHuv->C;
            $cSum += $c->LCHuv->H;
        }

        return self::withLCHuv($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithLCHuv(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        $aL = $this->LCHuv->L;
        $aC = $this->LCHuv->C;
        $aH = $this->LCHuv->H;
        $bL = $color->LCHuv->L;
        $bC = $color->LCHuv->C;
        $bH = $color->LCHuv->H;

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

        return self::withLCHuv(
            $aL + ($percentage * ($bL - $aL)),
            $aC + ($percentage * ($bC - $aC)),
            $H
        );
    }
}