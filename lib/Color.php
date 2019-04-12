<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

class Color {
    const ILLUMINANT_D65 = [ 0.95047, 1, 1.08883 ];
    const ILLUMINANT_D50 = [ 0.96422, 1, 0.82521 ];

    const WS_sRGB = '\dW\Pigmentum\WorkingSpace\RGB\sRGB';
    const WS_ADOBERGB1998 = '\dW\Pigmentum\WorkingSpace\RGB\AdobeRGB1998';

    const KAPPA = 903.296296296296296;
    const EPSILON = 0.008856451679036;

    public static $workingSpace = self::WS_sRGB;

    protected $_Lab;
    protected $_RGB;
    protected $_XYZ;

    protected static $cache = [];

    private function __construct(float $x, float $y, float $z, array $props = []) {
        $this->_XYZ = new ColorSpace\XYZ($x, $y, $z);

        if ($props !== []) {
            foreach ($props as $key => $value) {
                $key = "_$key";
                $this->$key = $value;
            }
        }
    }

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

        return self::_withRGB($r, $g, $b, $workingSpace, null, new ColorSpace\RGB\HSB($h, $s, $v));
    }

    static function withLab(float $L, float $a, float $b): Color {
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

        return new self($xr * self::ILLUMINANT_D50[0], $yr * self::ILLUMINANT_D50[1], $zr * self::ILLUMINANT_D50[2], [
            'Lab' => new ColorSpace\Lab($L, $a, $b)
        ]);
    }

    private static function _withRGB(float $r, float $g, float $b, string $workingSpace = null, string $hex = null, ColorSpace\RGB\HSB $hsb = null): Color {
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
        $color = new self($xyz[0], $xyz[1], $xyz[2], [ 'rgb' => new ColorSpace\RGB($r, $g, $b, $workingSpace, $hex, $hsb) ]);

        if ($workingSpace::illuminant !== self::ILLUMINANT_D50) {
            $color->XYZ->chromaticAdaptation(self::ILLUMINANT_D50, self::ILLUMINANT_D65);
        }

        return $color;
    }

    static function withRGB(float $r, float $g, float $b, string $workingSpace = null): Color {
        if (is_null($workingSpace)) {
            $workingSpace = self::$workingSpace;
        }

        return self::_withRGB($r, $g, $b, $workingSpace);
    }

    static function withXYZ(float $x, float $y, float $z): Color {
        // Can in some instances have values > 1. Illuminants are such an example.
        $x = ($x < 0.0) ? 0.0 : $x;
        $y = ($y < 0.0) ? 0.0 : $y;
        $z = ($z < 0.0) ? 0.0 : $z;

        return new self($x, $y, $z);
    }


    public function toLab(): ColorSpace\Lab {
        if (!is_null($this->_Lab)) {
            return $this->_Lab;
        }

        $xyz = $this->_XYZ;
        $cacheKey = "x{$xyz->x}_y{$xyz->y}_z{$xyz->z}";

        if (isset(self::$cache[$cacheKey]) && isset(self::$cache[$cacheKey]['Lab'])) {
            return self::$cache[$cacheKey]['Lab'];
        }

        $xyz = [
            $xyz->x / self::ILLUMINANT_D50[0],
            $xyz->y / self::ILLUMINANT_D50[1],
            $xyz->z / self::ILLUMINANT_D50[2]
        ];

        foreach ($xyz as &$m) {
            if ($m > self::EPSILON) {
                $m = $m ** (1/3);
            } else {
                $m = (self::KAPPA * $m + 16) / 116;
            }
        }

        $this->_Lab = new ColorSpace\Lab((116 * $xyz[1]) - 16, 500 * ($xyz[0] - $xyz[1]), 200 * ($xyz[1] - $xyz[2]));
        self::$cache[$cacheKey]['Lab'] = $this->_Lab;

        return $this->_Lab;
    }

    public function toRGB(string $workingSpace = null): ColorSpace\RGB {
        if (is_null($workingSpace)) {
            $workingSpace = self::$workingSpace;
        }

        $xyz = $this->_XYZ;
        $cacheKey = "x{$xyz->x}_y{$xyz->y}_z{$xyz->z}";

        if (isset(self::$cache[$cacheKey]) && isset(self::$cache[$cacheKey]['RGB'])) {
            return self::$cache[$cacheKey]['RGB'];
        }

        if ($workingSpace::illuminant !== self::ILLUMINANT_D50) {
            $xyz = (new ColorSpace\XYZ($this->_XYZ->x, $this->_XYZ->y, $this->_XYZ->z))->chromaticAdaptation(self::ILLUMINANT_D65, self::ILLUMINANT_D50);
        } else {
            $xyz = $this->_XYZ;
        }

        $matrix = $workingSpace::getXYZMatrix()->inverse();
        $uncompandedVector = $matrix->vectorMultiply(new Vector([ $xyz->x, $xyz->y, $xyz->z ]));

        $this->_RGB = new ColorSpace\RGB(
            $workingSpace::companding($uncompandedVector[0]) * 255,
            $workingSpace::companding($uncompandedVector[1]) * 255,
            $workingSpace::companding($uncompandedVector[2]) * 255,
            $workingSpace
        );

        return $this->_RGB;
    }

    // Average with RGB.
    public static function average(Color ...$colors): Color {
        $rsum = 0;
        $gsum = 0;
        $bsum = 0;
        $length = sizeof($colors);
        foreach ($colors as $c) {
            $rsum += $c->RGB->r;
            $gsum += $c->RGB->g;
            $bsum += $c->RGB->b;
        }

        return Color::withRGB($rsum / $length, $gsum / $length, $bsum / $length);
    }

    // Mix with L*a*b*.
    public function mix(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        $distance = $this->distance($color);
        $travelDistance = $distance - ($distance * ((100 - $percentage *= 100) / 100));
        $ratio = $travelDistance / $distance;

        return Color::withLab(
            $this->Lab->L + ($ratio * ($color->Lab->L - $this->Lab->L)),
            $this->Lab->a + ($ratio * ($color->Lab->a - $this->Lab->a)),
            $this->Lab->b + ($ratio * ($color->Lab->b - $this->Lab->b))
        );
    }

    public function distance(Color $color): float {
        return sqrt(($color->Lab->L - $this->Lab->L) ** 2 + ($color->Lab->a - $this->Lab->a) ** 2 + ($color->Lab->b - $this->Lab->b) ** 2);
    }

    public function difference(Color $color): float {
        return $this->deltaE($color);
    }

    // CIE2000
    public function deltaE(Color $color): float {
        $Lab1 = $this->Lab;
        $Lab2 = $color->Lab;

        $kL = 1.0;
	    $kC = 1.0;
	    $kH = 1.0;
	    $lBarPrime = 0.5 * ($Lab1->L + $Lab2->L);
	    $c1 = sqrt($Lab1->a ** 2 + $Lab1->b ** 2);
	    $c2 = sqrt($Lab2->a ** 2 + $Lab2->b ** 2);
	    $cBar = 0.5 * ($c1 + $c2);
	    $cBar7 = $cBar ** 7;
	    $g = 0.5 * (1.0 - sqrt($cBar7 / ($cBar7 + (25 ** 7))));
	    $a1Prime = $Lab1->a * (1.0 + $g);
	    $a2Prime = $Lab2->a * (1.0 + $g);
	    $c1Prime = sqrt($a1Prime ** 2 + $Lab1->b ** 2);
	    $c2Prime = sqrt($a2Prime ** 2 + $Lab2->b ** 2);
	    $cBarPrime = 0.5 * ($c1Prime + $c2Prime);

	    $h1Prime = (atan2($Lab1->b, $a1Prime) * 180.0) / M_PI;
        if ($h1Prime < 0.0) {
            $h1Prime += 360.0;
        }

	    $h2Prime = (atan2($Lab2->b, $a2Prime) * 180.0) / M_PI;
	    if ($h2Prime < 0.0) {
            $h2Prime += 360.0;
        }

	    $hBarPrime = (abs($h1Prime - $h2Prime) > 180.0) ? (0.5 * ($h1Prime + $h2Prime + 360.0)) : (0.5 * ($h1Prime + $h2Prime));
	    $t = 1.0 - 0.17 * cos(M_PI * ($hBarPrime - 30.0) / 180.0) + 0.24 * cos(M_PI * (2.0 * $hBarPrime) / 180.0) + 0.32 * cos(M_PI * (3.0 * $hBarPrime +  6.0) / 180.0) - 0.20 * cos(M_PI * (4.0 * $hBarPrime - 63.0) / 180.0);

        if (abs($h2Prime - $h1Prime) <= 180.0) {
            $dhPrime = $h2Prime - $h1Prime;
        } else {
            $dhPrime = ($h2Prime <= $h1Prime) ? ($h2Prime - $h1Prime + 360.0) : ($h2Prime - $h1Prime - 360.0);
        }

	    $dLPrime = $Lab2->L - $Lab1->L;
	    $dCPrime = $c2Prime - $c1Prime;
	    $dHPrime = 2.0 * sqrt($c1Prime * $c2Prime) * sin(M_PI * (0.5 * $dhPrime) / 180.0);
	    $sL = 1.0 + ((0.015 * ($lBarPrime - 50.0) * ($lBarPrime - 50.0)) / sqrt(20.0 + ($lBarPrime - 50.0) * ($lBarPrime - 50.0)));
	    $sC = 1.0 + 0.045 * $cBarPrime;
	    $sH = 1.0 + 0.015 * $cBarPrime * $t;
	    $dTheta = 30.0 * exp(0 - (($hBarPrime - 275.0) / 25.0) * (($hBarPrime - 275.0) / 25.0));
        $cBarPrime7 = $cBarPrime ** 7;
	    $rC = sqrt($cBarPrime7 / ($cBarPrime7 + (25 ** 7)));
	    $rT = -2.0 * $rC * sin(M_PI * (2.0 * $dTheta) / 180.0);

        return sqrt(
			($dLPrime / ($kL * $sL)) * ($dLPrime / ($kL * $sL)) +
			($dCPrime / ($kC * $sC)) * ($dCPrime / ($kC * $sC)) +
			($dHPrime / ($kH * $sH)) * ($dHPrime / ($kH * $sH)) +
			($dCPrime / ($kC * $sC)) * ($dHPrime / ($kH * $sH)) * $rT
        );
    }

    public function __get($property) {
        $prop = "_$property";
        if (property_exists($this, $prop)) {
            if (is_null($this->$prop)) {
                $method = "to$property";
                $this->$prop = $this->$method();
            }

            return $this->$prop;
        }
    }
}