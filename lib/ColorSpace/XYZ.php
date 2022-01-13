<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\ColorSpace\XYZ\LMS as ColorSpaceLMS;
use MathPHP\LinearAlgebra\MatrixFactory as MatrixFactory;
use MathPHP\LinearAlgebra\Matrix as Matrix;


class XYZ extends ColorSpace implements \Stringable {
    protected float $_X;
    protected float $_Y;
    protected float $_Z;

    protected ?ColorSpaceLMS $_LMS = null;

    const BRADFORD = [
        [ 0.8951000, 0.2664000, -0.1614000 ],
        [ -0.7502000, 1.7135000, 0.0367000 ],
        [ 0.0389000, -0.0685000, 1.0296000 ]
    ];


    public function __construct(float $X, float $Y, float $Z) {
        $this->_X = $X;
        $this->_Y = $Y;
        $this->_Z = $Z;
    }


    public function toLMS(): ColorSpaceLMS {
        if ($this->_LMS !== null) {
            return $this->_LMS;
        }

        $xyz = [ $this->_X, $this->_Y, $this->_Z ];
        $result = array_map(function($m) use($xyz) {
            $out = 0;
            $count = 0;
            foreach ($xyz as $key => $value) {
                $out += $m[$key] * $value;
            }

            return $out;
        }, self::BRADFORD);

        $this->_lms = new ColorSpaceLMS($result[0], $result[1], $result[2]);
        return $this->_lms;
    }

    // Bradford method of adaptation, seen as the most accurate to date.
    public function chromaticAdaptation(array $new, array $old): self {
        $new = (new XYZ($new[0], $new[1], $new[2]))->LMS;
        $old = (new XYZ($old[0], $old[1], $old[2]))->LMS;

        $mir = @MatrixFactory::create([
            [ $new->rho / $old->rho, 0, 0 ],
            [ 0, $new->gamma / $old->gamma, 0 ],
            [ 0, 0, $new->beta / $old->beta ]
        ]);

        $bradford = @MatrixFactory::create(self::BRADFORD);

        $m1 = $bradford->inverse()->multiply($mir);
        $m2 = $m1->multiply($bradford);
        $xyz = $m2->multiply(@MatrixFactory::create([ [$this->_X], [$this->_Y], [$this->_Z] ]));

        return new XYZ($xyz[0][0], $xyz[1][0], $xyz[2][0]);
    }


    public function __toString(): string {
        return "xyz({$this->_X}, {$this->_Y}, {$this->_Z})";
    }
}