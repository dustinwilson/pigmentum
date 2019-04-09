<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

class Color {
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

    static function withLab(float $L, float $a, float $b): Color {
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

    static function withRGB(int $r, int $g, int $b, string $workingSpace = self::WORKING_SPACE_RGB_sRGB): Color {
        $vector = [
            $r / 255,
            $g / 255,
            $b / 255
        ];

        $vector = new Vector(array_map("$workingSpace::inverseCompanding", $vector));
        $xyz = ($workingSpace::getXYZMatrix())->vectorMultiply($vector);

        $color = new self($xyz[0], $xyz[1], $xyz[2], [
            'rgb' => new ColorSpace\RGB($r, $g, $b, $workingSpace)
        ]);

        if ($workingSpace::illuminant !== self::ILLUMINANT_D50) {
            $color->xyz->chromaticAdaptation(self::ILLUMINANT_D50, self::ILLUMINANT_D65);
        }

        return $color;
    }

    static function withXYZ(float $x, float $y, float $z): Color {
        return new self($x, $y, $z);
    }


    public function toLMS(): ColorSpace\LMS {
        if (!is_null($this->_lms)) {
            return $this->_lms;
        }

        $xyz = $this->_xyz;
        $cacheKey = "x{$xyz->x}_y{$xyz->y}_z{$xyz->z}";

        if (isset(self::$cache[$cacheKey]) && isset(self::$cache[$cacheKey]['lms'])) {
            return self::$cache[$cacheKey]['lms'];
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

        $result = new ColorSpace\LMS($result[0], $result[1], $result[2]);

        self::$cache["x{$xyz[0]}_y{$xyz[1]}_z{$xyz[2]}"]['lms'] = $result;
        $this->_lms = $result;

        return $result;
    }

    public function toRGB(string $workingSpace = self::WORKING_SPACE_RGB_sRGB): ColorSpace\RGB {
        if ($workingSpace::illuminant !== self::ILLUMINANT_D50) {
            $xyz = (new ColorSpace\XYZ($this->_xyz->x, $this->_xyz->y, $this->_xyz->z))->chromaticAdaptation(self::ILLUMINANT_D65, self::ILLUMINANT_D50);
        } else {
            $xyz = $this->_xyz;
        }

        $matrix = $workingSpace::getXYZMatrix()->inverse();
        $uncompandedVector = $matrix->vectorMultiply(new Vector([ $xyz->x, $xyz->y, $xyz->z ]));

        $this->_rgb = new ColorSpace\RGB(
            (int)round($workingSpace::companding($uncompandedVector[0]) * 255),
            (int)round($workingSpace::companding($uncompandedVector[1]) * 255),
            (int)round($workingSpace::companding($uncompandedVector[2]) * 255),
            $workingSpace
        );

        return $this->_rgb;
    }


    // CIE2000
    public function difference(Color $color): float {
        // parametric weighting factors:
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
            if (is_null($this->$prop)) {
                switch ($property) {
                    case 'lms': $this->$prop = self::toLMS();
                    break;
                    case 'rgb': $this->$prop = self::toRGB();
                    break;
                }
            }

            return $this->$prop;
        }
    }
}