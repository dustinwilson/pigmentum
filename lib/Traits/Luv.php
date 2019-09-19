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

    private static function _withLuv(float $L, float $u, float $v, ColorSpaceLCHuv $LCHuv = null): Color {
        $u0 = (4 * Color::ILLUMINANT_D50[0]) / (Color::ILLUMINANT_D50[0] + 15 * Color::ILLUMINANT_D50[1] + 3 * Color::ILLUMINANT_D50[2]);
        $v0 = (9 * Color::ILLUMINANT_D50[0]) / (Color::ILLUMINANT_D50[0] + 15 * Color::ILLUMINANT_D50[1] + 3 * Color::ILLUMINANT_D50[2]);

        $Y = ($L > Color::KAPPA * Color::EPSILON) ? (($L + 16.0) / 116.0) ** 3 : $L / self::KAPPA;

        $a = (1 / 3) * ((52 * $L / ($u + 13 * $L * $u0)) - 1);
        $b = -5 * $Y;
        $c = 0 - (1 / 3);
        $d = $Y * ((39 * $L / $v + 13 * $L * $u0) - 5);

        $X = ($d - $b) / ($a - $c);
        $Z = $X * ($a + $b);

        return new self($X, $Y, $Z, [
            'Luv' => new ColorSpaceLuv($L, $u, $v),
            'LCHuv' => $LCHuv
        ]);
    }

    public static function withLuv(float $L, float $u, float $v): Color {
        return self::_withLuv($L, $u, $v);
    }

    public static function withLCHuv(float $L, float $C, float $H): Color {
        $hh = deg2rad($H);
        return self::withLuv($L, cos($hh) * $C, sin($hh) * $C, new ColorSpaceLCHuv($L, $C, $H));
    }

    public function toLuv(): ColorSpaceLuv {
        if (!is_null($this->_Luv)) {
            return $this->_Luv;
        }

        $xyz = $this->_XYZ;

        $yr = $xyz->y / Color::ILLUMINANT_D50[1];
        $uPrime = (4 * $xyz->x) / ($xyz->x + 15 * $xyz->y + 3 * $xyz->z);
        $vPrime = (9 * $xyz->y) / ($xyz->x + 15 * $xyz->y + 3 * $xyz->z);
        $uPrimeR = (4 * Color::ILLUMINANT_D50[0]) / (Color::ILLUMINANT_D50[0] + 15 * Color::ILLUMINANT_D50[1] + 3 * Color::ILLUMINANT_D50[2]);
        $vPrimeR = (9 * Color::ILLUMINANT_D50[1]) / (Color::ILLUMINANT_D50[0] + 15 * Color::ILLUMINANT_D50[1] + 3 * Color::ILLUMINANT_D50[2]);

        $L = ($yr > Color::EPSILON) ? 116 * ($yr ** (1 / 3)) - 16 : Color::KAPPA * $yr;
        $u = round(13 * $L * ($uPrime - $uPrimeR), 5);
        $v = round(13 * $L * ($vPrime - $vPrimeR), 5);
        $L = round($L, 5);

        // Combat issues where -0 would interfere in math down the road.
        $this->_Luv = new ColorSpaceLuv(($L == -0) ? 0 : $L, ($a == -0) ? 0 : $u, ($v == -0) ? 0 : $v);
        return $this->_Luv;
    }

    public function toLCHuv(): ColorSpaceLCHuv {
        if (is_null($this->_Luv)) {
            $this->toLuv();
        }

        $c = sqrt($this->_Luv->u**2 + $this->_Luv->v**2);

        $h = rad2deg(atan2($this->_Luv->v, $this->_Luv->u));
        if ($h < 0) {
            $h += 360;
        }

        $this->_LCHuv = new ColorSpaceLCHuv($this->_Luv->L, $c, $h);
        return $this->_LCHuv;
    }
}