<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use MathPHP\LinearAlgebra\Matrix as Matrix;

class XYZ extends AbstractSpace {
    protected $_x;
    protected $_y;
    protected $_z;

    protected $_lms;

    const BRADFORD = [
        [ 0.8951000, 0.2664000, -0.1614000 ],
        [ -0.7502000, 1.7135000, 0.0367000 ],
        [ 0.0389000, -0.0685000, 1.0296000 ]
    ];

    public function __construct(float $x, float $y, float $z) {
        $this->_x = $x;
        $this->_y = $y;
        $this->_z = $z;
    }

    protected function toLMS(): XYZ\LMS {
        if (!is_null($this->_lms)) {
            return $this->_lms;
        }

        $xyz = [ $this->_x, $this->_y, $this->_z ];
        $result = array_map(function($m) use($xyz) {
            $out = 0;
            $count = 0;
            foreach ($xyz as $key => $value) {
                $out += $m[$key] * $value;
            }

            return $out;
        }, self::BRADFORD);

        $this->_lms = new XYZ\LMS($result[0], $result[1], $result[2]);
        return $this->_lms;
    }

    // Bradford method of adaptation
    public function chromaticAdaptation(array $new, array $old): XYZ {
        $new = (new XYZ($new[0], $new[1], $new[2]))->toLMS();
        $old = (new XYZ($old[0], $old[1], $old[2]))->toLMS();

        $mir = new Matrix([
            [ $new->rho / $old->rho, 0, 0 ],
            [ 0, $new->gamma / $old->gamma, 0 ],
            [ 0, 0, $new->beta / $old->beta ]
        ]);

        $bradford = new Matrix(self::BRADFORD);

        $m1 = $bradford->inverse()->multiply($mir);
        $m2 = $m1->multiply($bradford);
        $xyz = $m2->multiply(new Matrix([ [$this->_x], [$this->_y], [$this->_z] ]));

        $this->_x = $xyz[0][0];
        $this->_y = $xyz[1][0];
        $this->_z = $xyz[2][0];

        return $this;
    }
}