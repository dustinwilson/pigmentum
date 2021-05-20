<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile;
use dW\Pigmentum\Color as Color;
use MathPHP\LinearAlgebra\MatrixFactory as MatrixFactory;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

abstract class RGB extends \dW\Pigmentum\Profile\Profile {
    const gamma = 2.2;

    // Gamma companding
    public static function companding(float $channel): float {
        return min(max($channel ** (1 / static::gamma), 0), 1);
    }

    public static function inverseCompanding(float $channel): float {
        return min(max($channel ** static::gamma, 0), 1);
    }

    public static function getXYZMatrix(): Matrix {
        $xr = static::chromaticity[0][0];
        $xg = static::chromaticity[1][0];
        $xb = static::chromaticity[2][0];
        $yr = static::chromaticity[0][1];
        $yg = static::chromaticity[1][1];
        $yb = static::chromaticity[2][1];

        $Xr = $xr / $yr;
        $Yr = 1;
        $Zr = (1 - $xr - $yr) / $yr;

        $Xg = $xg / $yg;
        $Yg = 1;
        $Zg = (1 - $xg - $yg) / $yg;

        $Xb = $xb / $yb;
        $Yb = 1;
        $Zb = (1 - $xb - $yb) / $yb;

        $S = (MatrixFactory::create([
            [ $Xr, $Xg, $Xb ],
            [ $Yr, $Yg, $Yb ],
            [ $Zr, $Zg, $Zb ]
        ]))->inverse();

        $W = new Vector(static::illuminant);
        $SW = $S->multiply($W);

        $Sr = $SW->getRow(0)[0];
        $Sg = $SW->getRow(1)[0];
        $Sb = $SW->getRow(2)[0];

        return MatrixFactory::create([
            [ $Sr * $Xr, $Sg * $Xg, $Sb * $Xb ],
            [ $Sr * $Yr, $Sg * $Yg, $Sb * $Yb ],
            [ $Sr * $Zr, $Sg * $Zg, $Sb * $Zb ],
        ]);
    }
}