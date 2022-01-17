<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\ColorSpace\XYZ\LMS as ColorSpaceLMS;
use dW\Pigmentum\Math;


class XYZ extends ColorSpace implements \Stringable {
    protected float $_X;
    protected float $_Y;
    protected float $_Z;

    protected ?ColorSpaceLMS $_LMS = null;

    const BRADFORD = [
        [ 0.8951, 0.2664, -0.1614 ],
        [ -0.7502, 1.7135, 0.0367 ],
        [ 0.0389, -0.0685, 1.0296 ]
    ];

    const BRADFORD_INVERSE = array (
    0 =>
    array (
      0 => 0.9869929054667121,
      1 => -0.14705425642099013,
      2 => 0.15996265166373122,
    ),
    1 =>
    array (
      0 => 0.4323052697233945,
      1 => 0.5183602715367774,
      2 => 0.049291228212855594,
    ),
    2 =>
    array (
      0 => -0.008528664575177328,
      1 => 0.04004282165408486,
      2 => 0.96848669578755,
    ),
  );


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

        $mir = [
            [ $new->rho / $old->rho, 0, 0 ],
            [ 0, $new->gamma / $old->gamma, 0 ],
            [ 0, 0, $new->beta / $old->beta ]
        ];

        $m1 = Math::multiply3x3Matrix(self::BRADFORD_INVERSE, $mir);
        $m2 = Math::multiply3x3Matrix($m1, self::BRADFORD);
        return new XYZ(...Math::multiply3x3MatrixVector($m2, [ $this->_X, $this->_Y, $this->_Z ]));
    }


    public function __toString(): string {
        return "xyz({$this->_X}, {$this->_Y}, {$this->_Z})";
    }
}