<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

class Color {
    protected $_lms;
    protected $_rgb;
    protected $_xyz;

    protected static $cache = [];

    const BRADFORD = [
        [ 0.8951000, 0.2664000, -0.1614000 ],
        [ -0.7502000, 1.7135000, 0.0367000 ],
        [ 0.0389000, -0.0685000, 1.0296000 ]
    ];

    const INVERSE_BRADFORD = [
        [ 0.9869929, -0.1470543, 0.1599627 ],
        [ 0.4323053, 0.5183603, 0.0492912 ],
        [ -0.0085287, 0.0400428, 0.9684867 ]
    ];

    const ILLUMINANT_D65 = [ 0.95047, 1, 1.08883 ];
    const ILLUMINANT_D50 = [ 0.96422, 1, 0.82521 ];

    const WORKING_SPACE_RGB_sRGB = '\dW\Pigmentum\WorkingSpace\RGB\sRGB';
    const WORKING_SPACE_RGB_ADOBERGB1998 = '\dW\Pigmentum\WorkingSpace\RGB\AdobeRGB1998';

    private function __construct(float $x, float $y, float $z, array $props = []) {
        $this->_xyz = new ColorSpace\XYZ($x, $y, $z);

        if ($props !== []) {
            foreach ($props as $key => $value) {
                $key = "_$key";
                $this->$key = $value;
            }
        }
    }

    static function withXYZ(float $x, float $y, float $z): Color {
        return new self($x, $y, $z);
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
            $color->chromaticAdaptation(self::ILLUMINANT_D50, self::ILLUMINANT_D65);
        }

        return $color;
    }

    public function toRGB(string $workingSpace = self::WORKING_SPACE_RGB_sRGB): ColorSpace\RGB {
        if ($workingSpace::illuminant !== self::ILLUMINANT_D50) {
            $xyz = (new self($this->_xyz->x, $this->_xyz->y, $this->_xyz->z))->chromaticAdaptation(self::ILLUMINANT_D65, self::ILLUMINANT_D50)->xyz;
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
        }, self::BRADFORD);

        $result = new ColorSpace\LMS($result[0], $result[1], $result[2]);

        self::$cache["x{$xyz[0]}_y{$xyz[1]}_z{$xyz[2]}"]['lms'] = $result;
        $this->_lms = $result;

        return $result;
    }

    // Bradford method of adaptation
    protected function chromaticAdaptation(array $new, array $old): Color {
        $new = (self::withXYZ($new[0], $new[1], $new[2]))->toLMS();
        $old = (self::withXYZ($old[0], $old[1], $old[2]))->toLMS();

        $mir = new Matrix([
            [ $new->rho / $old->rho, 0, 0 ],
            [ 0, $new->gamma / $old->gamma, 0 ],
            [ 0, 0, $new->beta / $old->beta]
        ]);

        $m1 = (new Matrix(self::INVERSE_BRADFORD))->multiply($mir);
        $m2 = $m1->multiply(new Matrix(self::BRADFORD));
        $xyz = $m2->multiply(new Matrix([ [$this->_xyz->x], [$this->_xyz->y], [$this->_xyz->z] ]));

        $this->_xyz = new ColorSpace\XYZ($xyz[0][0], $xyz[1][0], $xyz[2][0]);

        return $this;
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