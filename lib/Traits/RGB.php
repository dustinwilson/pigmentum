<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\ColorSpace\RGB\HSB as ColorSpaceHSB;
use dW\Pigmentum\ColorSpace\RGB as ColorSpaceRGB;
use dW\Pigmentum\ColorSpace\XYZ as ColorSpaceXYZ;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

trait RGB {
    protected $_Hex;
    protected $_HSB;
    protected $_RGB;


    static function withHex(string $hex, ?string $name = null, int $profile = -1): Color {
        if ($profile === -1) {
            $profile = self::$workingSpaceRGB;
        }

        if (strpos($hex, '#') !== 0) {
            $hex = "#$hex";
        }

        if (strlen($hex) !== 7) {
            throw new \Exception(sprintf('"%s" is an invalid 8-bit RGB hex string', $hex));
        }

        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return self::_withRGB($r, $g, $b, $name, $profile, $hex);
    }

    static function withHSB(float $h, float $s, float $v, ?string $name = null, int $profile = -1): Color {
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

    private static function _withRGB(float $r, float $g, float $b, ?string $name = null, int $profile = -1, ?string $hex = null, ?ColorSpaceHSB $hsb = null): Color {
        if ($profile === -1) {
            $profile = self::$workingSpaceRGB;
        }
        $profileClass = self::getProfileClassString($profile);

        $r = min(max($r, 0), 255);
        $g = min(max($g, 0), 255);
        $b = min(max($b, 0), 255);

        $vector = new Vector([
            $profileClass::inverseCompanding($r / 255),
            $profileClass::inverseCompanding($g / 255),
            $profileClass::inverseCompanding($b / 255)
        ]);

        $xyz = ($profileClass::getXYZMatrix())->vectorMultiply($vector);
        $color = new self($xyz[0], $xyz[1], $xyz[2], $name, [
            'RGB' => new ColorSpaceRGB($r, $g, $b, $profile),
            'Hex' => $hex,
            'HSB' => $hsb
        ]);

        if ($profileClass::illuminant !== self::REFERENCE_WHITE) {
            $color->XYZ->chromaticAdaptation(self::REFERENCE_WHITE, $profileClass::illuminant);
        }

        return $color;
    }

    public static function withRGB(float $r, float $g, float $b, ?string $name = null, int $profile = -1): Color {
        if ($profile === -1) {
            $profile = self::$workingSpaceRGB;
        }
        return self::_withRGB($r, $g, $b, $name, $profile);
    }


    public function toHex(): string {
        if (is_null($this->_RGB)) {
            $this->toRGB();
        }

        if (!is_null($this->_hex)) {
            return $this->_hex;
        }

        $this->_hex = sprintf("#%02x%02x%02x", (int)round($this->_RGB->R), (int)round($this->_RGB->G), (int)round($this->_RGB->B));
        return $this->_hex;
    }

    public function toHSB(): ColorSpaceHSB {
        if (is_null($this->_RGB)) {
            $this->toRGB();
        }

        if (!is_null($this->_hsb)) {
            return $this->_hsb;
        }

        $r = $this->RGB->R / 255;
        $g = $this->_RGB->G / 255;
        $b = $this->_RGB->B / 255;

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

        $this->_hsb = new ColorSpaceHSB($h * 360, $s * 100, $v * 100);
        return $this->_hsb;
    }


    public function toRGB(int $profile = -1): ColorSpaceRGB {
        if ($profile === -1) {
            $profile = self::$workingSpaceRGB;
        }
        $profileClass = self::getProfileClassString($profile);
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
                $profile
            );
        } else {
            if ($profileClass::illuminant !== self::REFERENCE_WHITE) {
                $xyz = (new ColorSpaceXYZ($this->_XYZ->X, $this->_XYZ->Y, $this->_XYZ->Z))->chromaticAdaptation(self::ILLUMINANT_D65, self::REFERENCE_WHITE);
            } else {
                $xyz = $this->_XYZ;
            }

            $matrix = $profileClass::getXYZMatrix()->inverse();
            $uncompandedVector = $matrix->vectorMultiply(new Vector([ $xyz->X, $xyz->Y, $xyz->Z ]));

            $this->_RGB = new ColorSpaceRGB(
                min(max($profileClass::companding($uncompandedVector[0]) * 255, 0), 255),
                min(max($profileClass::companding($uncompandedVector[1]) * 255, 0), 255),
                min(max($profileClass::companding($uncompandedVector[2]) * 255, 0), 255),
                $profile
            );
        }

        $this->_Hex = null;

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
            $aSum += $c->HSB->H;
            $bSum += $c->HSB->S;
            $cSum += $c->HSB->B;
        }

        return self::withHSB($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithHSB(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        $aH = $this->HSB->H;
        $aS = $this->HSB->S;
        $aB = $this->HSB->B;
        $bH = $color->HSB->H;
        $bS = $color->HSB->S;
        $bB = $color->HSB->B;

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

    protected function changeProfileRGB(int $profile = -1): bool {
        self::getProfileClassString($profile);

        self::toRGB($profile);
        if ($this->_HSB !== null) {
            self::toHSB();
        }

        return true;
    }
}