<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\ColorSpace\RGB\HSB as ColorSpaceHSB;
use dW\Pigmentum\ColorSpace\RGB as ColorSpaceRGB;
use dW\Pigmentum\ColorSpace\XYZ as ColorSpaceXYZ;
use dW\Pigmentum\Profile\RGB as RGBProfile;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

trait RGB {
    protected $_RGB;


    static function withRGBHex(string $hex, ?string $name = null, ?string $profile = null): Color {
        $profile = self::validateRGBProfile($profile);

        if (strpos($hex, '#') !== 0) {
            $hex = "#$hex";
        }

        if (strlen($hex) !== 7) {
            throw new \Exception(sprintf('"%s" is an invalid 8-bit RGB hex string', $hex));
        }

        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return self::_withRGB($r, $g, $b, $name, $profile, $hex);
    }

    static function withHSB(float $h, float $s, float $v, ?string $name = null, ?string $profile = null): Color {
        $profile = self::validateRGBProfile($profile);

        $ss = $s / 100;
        $vv = $v / 100 * 255;

        if ($s == 0) {
            $r = $g = $b = $vv;
        } else {
            if ($h === 360) {
                $h = 0;
            }

            if ($h > 360) {
                $h -= 360;
            }

            if ($h < 0) {
                $h += 360;
            }

            $hh = $h / 60;

            $i = floor($hh);
            $f = $hh - $i;
            $p = $vv * (1 - $ss);
            $q = $vv * (1 - $ss * $f);
            $t = $vv * (1 - $ss * (1 - $f));

            switch ($i) {
                case 0:
                    $r = $vv;
                    $g = $t;
                    $b = $p;
                break;
                case 1:
                    $r = $q;
                    $g = $vv;
                    $b = $p;
                break;
                case 2:
                    $r = $p;
                    $g = $vv;
                    $b = $t;
                break;
                case 3:
                    $r = $p;
                    $g = $q;
                    $b = $vv;
                break;
                case 4:
                    $r = $t;
                    $g = $p;
                    $b = $vv;
                break;
                default:
                    $r = $vv;
                    $g = $p;
                    $b = $q;
            }
        }

        return self::_withRGB($r, $g, $b, $name, $profile, null, new ColorSpaceHSB($h, $s, $v));
    }

    private static function _withRGB(float $r, float $g, float $b, ?string $name = null, ?string $profile = null, ?string $hex = null, ?ColorSpaceHSB $HSB = null): Color {
        $profile = self::validateRGBProfile($profile);

        $r = min(max($r, 0), 255);
        $g = min(max($g, 0), 255);
        $b = min(max($b, 0), 255);

        $vector = new Vector([
            $profile::inverseCompanding($r / 255),
            $profile::inverseCompanding($g / 255),
            $profile::inverseCompanding($b / 255)
        ]);

        $xyz = ($profile::getXYZMatrix())->vectorMultiply($vector);
        $xyz = new ColorSpaceXYZ($xyz[0], $xyz[1], $xyz[2]);

        if ($profile::illuminant !== self::REFERENCE_WHITE) {
            $xyz = $xyz->chromaticAdaptation(self::REFERENCE_WHITE, $profile::illuminant);
        }

        $color = new self($xyz->X, $xyz->Y, $xyz->Z, $name, [
            'RGB' => new ColorSpaceRGB($r, $g, $b, $profile, $xyz, $hex, $HSB)
        ]);

        return $color;
    }

    public static function withRGB(float $r, float $g, float $b, ?string $name = null, ?string $profile = null): Color {
        return self::_withRGB($r, $g, $b, $name, $profile);
    }

    public function toRGB(?string $profile = null): ColorSpaceRGB {
        $profile = self::validateRGBProfile($profile);

        $xyz = $this->_XYZ;

        // If the XYZ value is within 5 decimal points of D50 (illuminant used by this
        // implementation's XYZ) then it should be treated as white, otherwise convert it.
        if (array_map(function($n) {
            return round($n, 5);
        }, [ $xyz->X, $xyz->Y, $xyz->Z ]) == self::REFERENCE_WHITE) {
            $this->_RGB = new ColorSpaceRGB(
                255,
                255,
                255,
                $profile,
                $this->_XYZ
            );
        } else {
            if ($profile::illuminant != self::REFERENCE_WHITE) {
                $xyz = $this->_XYZ->chromaticAdaptation($profile::illuminant, self::REFERENCE_WHITE);
            } else {
                $xyz = $this->_XYZ;
            }

            $matrix = $profile::getXYZMatrix()->inverse();
            $uncompandedVector = $matrix->vectorMultiply(new Vector([ $xyz->X, $xyz->Y, $xyz->Z ]));

            $this->_RGB = new ColorSpaceRGB(
                min(max($profile::companding($uncompandedVector[0]) * 255, 0), 255),
                min(max($profile::companding($uncompandedVector[1]) * 255, 0), 255),
                min(max($profile::companding($uncompandedVector[2]) * 255, 0), 255),
                $profile,
                $this->_XYZ
            );
        }

        return $this->_RGB;
    }


    public static function averageWithRGB(Color ...$colors): Color {
        $aSum = 0;
        $bSum = 0;
        $cSum = 0;
        $length = sizeof($colors);

        foreach ($colors as $c) {
            $aSum += $c->RGB->R;
            $bSum += $c->RGB->G;
            $cSum += $c->RGB->B;
        }

        return self::withRGB($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public static function average(Color ...$colors): Color {
        return self::averageWithRGB(...$colors);
    }

    public function mixWithRGB(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        return self::withRGB(
            $this->RGB->R + ($percentage * ($color->RGB->R - $this->RGB->R)),
            $this->RGB->G + ($percentage * ($color->RGB->G - $this->RGB->G)),
            $this->RGB->B + ($percentage * ($color->RGB->B - $this->RGB->B))
        );
    }

    public static function averageWithHSB(Color ...$colors): Color {
        $aSum = 0;
        $bSum = 0;
        $cSum = 0;
        $length = sizeof($colors);

        foreach ($colors as $c) {
            $aSum += $c->RGB->HSB->H;
            $bSum += $c->RGB->HSB->S;
            $cSum += $c->RGB->HSB->B;
        }

        return self::withHSB($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithHSB(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        $aH = $this->RGB->HSB->H;
        $aS = $this->RGB->HSB->S;
        $aB = $this->RGB->HSB->B;
        $bH = $color->RGB->HSB->H;
        $bS = $color->RGB->HSB->S;
        $bB = $color->RGB->HSB->B;

        // If the saturation is 0 then the hue doesn't matter. The color is
        // grey, so to keep mixing from going across the entire hue range in
        // some cases...
        if ($aS == 0) {
            $aH = $bH;
        } elseif ($bS == 0) {
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

        return self::withHSB(
            $H,
            $aS + ($percentage * ($bS - $aS)),
            $aB + ($percentage * ($bB - $aB))
        );
    }

    protected static function validateRGBProfile(?string $profile = null): string {
        if ($profile !== null) {
            // This whole process of passing around profiles as strings is stupid as
            // evidenced by the line below, but PHP has no way of handling passing around
            // static classes yet.
            if (!in_array(RGBProfile::class, class_parents($profile))) {
                throw new \Exception("$profile is not an instance of " . RGBProfile::class . ".\n");
            }

            return $profile;
        }

        return self::$workingSpaceRGB;
    }
}