<?php
declare(strict_types=1);
namespace dW\Pigmentum\Traits;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\ColorSpace\RGB\HSB as ColorSpaceHSB;
use dW\Pigmentum\ColorSpace\RGB as ColorSpaceRGB;
use dW\Pigmentum\ColorSpace\XYZ as ColorSpaceXYZ;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

trait RGB {
    public static $workingSpace = self::WS_sRGB;

    protected $_Hex;
    protected $_HSB;
    protected $_RGB;


    static function withHex(string $hex, string $workingSpace = null): Color {
        if (is_null($workingSpace)) {
            $workingSpace = self::$workingSpace;
        }

        if (strpos($hex, '#') !== 0) {
            $hex = "#$hex";
        }

        if (strlen($hex) !== 7) {
            throw new \Exception(sprintf('"%s" is an invalid 8-bit RGB hex string', $hex));
        }

        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return self::_withRGB($r, $g, $b, $workingSpace, $hex);
    }

    static function withHSB(float $h, float $s, float $v, string $workingSpace = null): Color {
        if (is_null($workingSpace)) {
            $workingSpace = self::$workingSpace;
        }

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

        return self::_withRGB($r, $g, $b, $workingSpace, null, new ColorSpaceHSB($h, $s, $v));
    }

    private static function _withRGB(float $r, float $g, float $b, string $workingSpace = null, $hex = null, ColorSpaceHSB $hsb = null): Color {
        if (is_null($workingSpace)) {
            $workingSpace = self::$workingSpace;
        }

        $r = min(max($r, 0), 255);
        $g = min(max($g, 0), 255);
        $b = min(max($b, 0), 255);

        $vector = new Vector([
            $workingSpace::inverseCompanding($r / 255),
            $workingSpace::inverseCompanding($g / 255),
            $workingSpace::inverseCompanding($b / 255)
        ]);

        $xyz = ($workingSpace::getXYZMatrix())->vectorMultiply($vector);
        $color = new self($xyz[0], $xyz[1], $xyz[2], [
            'RGB' => new ColorSpaceRGB($r, $g, $b, $workingSpace),
            'Hex' => $hex,
            'HSB' => $hsb
        ]);

        if ($workingSpace::illuminant !== Color::ILLUMINANT_D50) {
            $color->XYZ->chromaticAdaptation(Color::ILLUMINANT_D50, Color::ILLUMINANT_D65);
        }

        return $color;
    }

    public static function withRGB(float $r, float $g, float $b, string $workingSpace = null): Color {
        return self::_withRGB($r, $g, $b, $workingSpace);
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


    public function toRGB(string $workingSpace = null): ColorSpaceRGB {
        if (is_null($workingSpace)) {
            $workingSpace = self::$workingSpace;
        }

        $xyz = $this->_XYZ;

        if ($workingSpace::illuminant !== Color::ILLUMINANT_D50) {
            $xyz = (new ColorSpaceXYZ($this->_XYZ->X, $this->_XYZ->Y, $this->_XYZ->Z))->chromaticAdaptation(Color::ILLUMINANT_D65, Color::ILLUMINANT_D50);
        } else {
            $xyz = $this->_XYZ;
        }

        $matrix = $workingSpace::getXYZMatrix()->inverse();
        $uncompandedVector = $matrix->vectorMultiply(new Vector([ $xyz->X, $xyz->Y, $xyz->Z ]));

        $this->_RGB = new ColorSpaceRGB(
            min(max($workingSpace::companding($uncompandedVector[0]) * 255, 0), 255),
            min(max($workingSpace::companding($uncompandedVector[1]) * 255, 0), 255),
            min(max($workingSpace::companding($uncompandedVector[2]) * 255, 0), 255),
            $workingSpace
        );

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

        return Color::withRGB($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public static function average(Color ...$colors): Color {
        return Color::averageWithRGB(...$colors);
    }

    public function mixWithRGB(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        return Color::withRGB(
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

        return Color::withHSB($aSum / $length, $bSum / $length, $cSum / $length);
    }

    public function mixWithHSB(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        return Color::withHSB(
            $this->HSB->H + ($percentage * ($color->HSB->H - $this->HSB->H)),
            $this->HSB->S + ($percentage * ($color->HSB->S - $this->HSB->S)),
            $this->HSB->B + ($percentage * ($color->HSB->B - $this->HSB->B))
        );
    }
}