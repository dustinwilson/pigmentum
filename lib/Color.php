<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

class Color {
    protected $_hsb;
    protected $_Lab;
    protected $_lms;
    protected $_rgb;
    protected $_xyz;

    protected static $cache = [];

    const ILLUMINANT_D65 = [ 0.95047, 1, 1.08883 ];
    const ILLUMINANT_D50 = [ 0.96422, 1, 0.82521 ];

    const WORKING_SPACE_RGB_sRGB = '\dW\Pigmentum\WorkingSpace\RGB\sRGB';
    const WORKING_SPACE_RGB_ADOBERGB1998 = '\dW\Pigmentum\WorkingSpace\RGB\AdobeRGB1998';

    const KAPPA = 903.296296296296296;
    const EPSILON = 0.008856451679036;

    private function __construct(float $x, float $y, float $z, array $props = []) {
        $this->_xyz = new ColorSpace\XYZ($x, $y, $z);

        if ($props !== []) {
            foreach ($props as $key => $value) {
                $key = "_$key";
                $this->$key = $value;
            }
        }
    }

    static function withHex(string $hex, string $workingSpace = self::WORKING_SPACE_RGB_sRGB): Color {
        if (strpos($hex, '#') !== 0) {
            $hex = "#$hex";
        }

        if (strlen($hex) !== 7) {
            throw new \Exception(sprintf('"%s" is an invalid 8-bit RGB hex string', $hex));
        }

        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return self::_withRGB($r, $g, $b, $workingSpace, $hex);
    }

    static function withHSB(float $h, float $s, float $v, string $workingSpace = self::WORKING_SPACE_RGB_sRGB): Color {
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

        return self::_withRGB($r, $g, $b, $workingSpace, null, [
            'hsb' => new ColorSpace\HSB($h, $s, $v, $workingSpace)
        ]);
    }

    static function withLab(float $L, float $a, float $b): Color {
        $L = min(max($r, 0), 100);
        $a = min(max($g, -128), 127);
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

    private static function _withRGB(float $r, float $g, float $b, string $workingSpace = self::WORKING_SPACE_RGB_sRGB, string $hex = null, array $options = null): Color {
        $r = min(max($r, 0), 255);
        $g = min(max($g, 0), 255);
        $b = min(max($b, 0), 255);

        $vector = new Vector([
            $workingSpace::inverseCompanding($r / 255),
            $workingSpace::inverseCompanding($g / 255),
            $workingSpace::inverseCompanding($b / 255)
        ]);

        $xyz = ($workingSpace::getXYZMatrix())->vectorMultiply($vector);

        $o = [ 'rgb' => new ColorSpace\RGB($r, $g, $b, $workingSpace, $hex) ];
        $options = (is_null($options)) ? $o : array_merge($o, $options);
        $color = new self($xyz[0], $xyz[1], $xyz[2], $options);

        if ($workingSpace::illuminant !== self::ILLUMINANT_D50) {
            $color->xyz->chromaticAdaptation(self::ILLUMINANT_D50, self::ILLUMINANT_D65);
        }

        return $color;
    }

    static function withRGB(float $r, float $g, float $b, string $workingSpace = self::WORKING_SPACE_RGB_sRGB): Color {
        return self::_withRGB($r, $g, $b, $workingSpace);
    }

    static function withXYZ(float $x, float $y, float $z): Color {
        $x = min(max($x, 0), 1);
        $y = min(max($y, 0), 1);
        $z = min(max($z, 0), 1);

        return new self($x, $y, $z);
    }


    public function toLab(): ColorSpace\Lab {
        if (!is_null($this->_Lab)) {
            return $this->_Lab;
        }

        $xyz = $this->_xyz;
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

    public function toLMS(): ColorSpace\LMS {
        if (!is_null($this->_lms)) {
            return $this->_lms;
        }

        $xyz = $this->_xyz;
        $cacheKey = "x{$xyz->x}_y{$xyz->y}_z{$xyz->z}";

        if (isset(self::$cache[$cacheKey]) && isset(self::$cache[$cacheKey]['LMS'])) {
            return self::$cache[$cacheKey]['LMS'];
        }

        $xyz = [ $xyz->x, $xyz->y, $xyz->z ];
        $result = array_map(function($m) use($xyz) {
            $out = 0;
            $count = 0;
            foreach ($xyz as $key => $value) {
                $out += $m[$key] * $value;
            }

            return $out;
        }, ColorSpace\XYZ::BRADFORD);

        $this->_lms = new ColorSpace\LMS($result[0], $result[1], $result[2]);
        self::$cache[$cacheKey]['lms'] = $this->_lms;

        return $this->_lms;
    }

    public function toRGB(string $workingSpace = self::WORKING_SPACE_RGB_sRGB): ColorSpace\RGB {
        $xyz = $this->_xyz;
        $cacheKey = "x{$xyz->x}_y{$xyz->y}_z{$xyz->z}";

        if (isset(self::$cache[$cacheKey]) && isset(self::$cache[$cacheKey]['RGB'])) {
            return self::$cache[$cacheKey]['RGB'];
        }

        if ($workingSpace::illuminant !== self::ILLUMINANT_D50) {
            $xyz = (new ColorSpace\XYZ($this->_xyz->x, $this->_xyz->y, $this->_xyz->z))->chromaticAdaptation(self::ILLUMINANT_D65, self::ILLUMINANT_D50);
        } else {
            $xyz = $this->_xyz;
        }

        $matrix = $workingSpace::getXYZMatrix()->inverse();
        $uncompandedVector = $matrix->vectorMultiply(new Vector([ $xyz->x, $xyz->y, $xyz->z ]));

        $this->_rgb = new ColorSpace\RGB(
            $workingSpace::companding($uncompandedVector[0]) * 255,
            $workingSpace::companding($uncompandedVector[1]) * 255,
            $workingSpace::companding($uncompandedVector[2]) * 255,
            $workingSpace
        );

        return $this->_rgb;
    }


    // CIE2000
    public function difference(Color $color): float {
        $k_H = 1;
        $k_L = 1;
        $k_C = 1;

        $x = $this->_lab;
        $y = $color->lab;

        $a0 = $x->a;
        $a1 = $y->a;
        $b0 = $x->b;
        $b1 = $y->b;

        $C_ab0 = sqrt($a0 * $a0 + $b0 * $b0);
        $C_ab1 = sqrt($a1 * $a1 + $b1 * $b1);

        $C_ab_mean = ($C_ab0 + $C_ab1) / 2;

        $G = 0.5 * (1 - sqrt($C_ab_mean ** 7 / $C_ab_mean ** 7 + 25 ** 7));

        $a_prime0 = (1 + $G) * $a0;
        $a_prime1 = (1 + $G) * $a1;
        $C_prime0 = sqrt($a_prime0 * $a_prime0 + $b0 * $b0);
        $C_prime1 = sqrt($a_prime1 * $a_prime1 + $b1 * $b1);

        $hRadians = atan2($b0, $a_prime0);
        $h_prime0 = rad2deg($hRadians);
        $hRadians = atan2($b1, $a_prime1);
        $h_prime1 = rad2deg($hRadians);

        $dL_prime = $y->L - $x->L;;
        $dC_prime = $C_prime1 - $C_prime0;

        if ($C_prime0 * $C_prime1 === 0) {
            $dh_prime = 0;
        } elseif (abs($h_prime1 - $h_prime0) <= 180) {
            $dh_prime = $h_prime1 - $h_prime0;
        } elseif ($h_prime1 - $h_prime0 > 180) {
            $dh_prime = $h_prime1 - $h_prime0 - 360;
        } elseif ($h_prime1 - $h_prime0 < -180) {
            $dh_prime = $h_prime1 - $h_prime0 + 360;
        } else {
            $dh_prime = 0;
        }

        $DH_prime = 2 * sqrt($C_prime0 * $C_prime1) * sin($dh_prime / 2);
        $L_prime_mean = ($x->L + $y->L) / 2;
        $C_prime_mean = ($C_prime0 + $C_prime1) / 2;

        if ($C_prime0 * $C_prime1 == 0) {
            $h_prime_mean = $h_prime0 + $h_prime1;
        } elseif (abs($h_prime0 - $h_prime1) <= 180) {
            $h_prime_mean = ($h_prime0 + $h_prime1) / 2;
        } elseif (abs($h_prime0 - $h_prime1) > 180 && $h_prime0 + $h_prime1 < 360) {
            $h_prime_mean = ($h_prime0 + $h_prime1 + 360) / 2;
        } elseif (abs($h_prime0 - $h_prime1) > 180 && $h_prime0 + $h_prime1 >= 360) {
            $h_prime_mean = ($h_prime0 + $h_prime1 - 360) / 2;
        } else {
            $h_prime_mean = $h_prime0 + $h_prime1;
        }

        $T = 1 - 0.17 * cos($h_prime_mean - 30) + 0.24 * cos(2 * $h_prime_mean) + 0.32 * cos(3 * $h_prime_mean + 6) - 0.20 * cos(4 * $h_prime_mean - 63);
        $dTheta = 30 * exp(0 - (($h_prime_mean - 275) / 25) ** 2);
        $R_C = 2 * sqrt($C_prime_mean ** 7 / ($C_prime_mean ** 7 + 25 ** 7));
        $S_L = 1 + 0.015 * ($L_prime_mean - 50) ** 2 / sqrt(20 + ($L_prime_mean - 50) ** 2);
        $S_C = 1 + 0.045 * $C_prime_mean;
        $S_H = 1 + 0.015 * $C_prime_mean * $T;
        $R_T = 0 - sin(2 * $dTheta) * $R_C;

        return sqrt(
            ($dL_prime / ($k_L * $S_L)) ** 2 +
            ($dC_prime / ($k_C * $S_C)) ** 2 +
            ($dH_prime / ($k_H * $S_H)) ** 2 +
            $R_T * ($dC_prime / ($k_C * $S_C)) * ($dH_prime / ($k_H * $S_H))
        );
    }

    public function __get($property) {
        $prop = "_$property";
        if (property_exists($this, $prop)) {
            return $this->$prop;
        }
    }
}