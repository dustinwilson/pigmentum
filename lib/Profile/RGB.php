<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile;
use dW\Pigmentum\{
    Color,
    Math
};


abstract class RGB extends \dW\Pigmentum\Profile\Profile {
    const gamma = 2.2;

    protected static array $xyzMatrix = [];
    protected static array $xyzMatrixInverse = [];

    // Gamma companding
    public static function companding(float $channel): float {
        return min(max($channel ** (1 / static::gamma), 0), 1);
    }

    public static function inverseCompanding(float $channel): float {
        return min(max($channel ** static::gamma, 0), 1);
    }

    public static function getXYZMatrix(): array {
        if (static::$xyzMatrix !== []) {
            return static::$xyzMatrix;
        }

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

        $S = Math::invert3x3Matrix([
            [ $Xr, $Xg, $Xb ],
            [ $Yr, $Yg, $Yb ],
            [ $Zr, $Zg, $Zb ]
        ]);

        $SW = Math::multiply3x3MatrixVector($S, static::illuminant);
        $Sr = $SW[0];
        $Sg = $SW[1];
        $Sb = $SW[2];

        static::$xyzMatrix = [
            [ $Sr * $Xr, $Sg * $Xg, $Sb * $Xb ],
            [ $Sr * $Yr, $Sg * $Yg, $Sb * $Yb ],
            [ $Sr * $Zr, $Sg * $Zg, $Sb * $Zb ],
        ];

        return static::$xyzMatrix;
    }

    public static function getXYZMatrixInverse(): array {
        if (static::$xyzMatrixInverse !== []) {
            return static::$xyzMatrixInverse;
        }

        static::$xyzMatrixInverse = Math::invert3x3Matrix(self::getXYZMatrix());
        return static::$xyzMatrixInverse;
    }
}