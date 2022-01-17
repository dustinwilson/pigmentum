<?php
declare(strict_types=1);
namespace dW\Pigmentum;

class Math {
    public static function invert3x3Matrix(array $matrix): array {
        // Get the determinant
        $scale = 1 / ($matrix[0][0] * ($matrix[2][2] * $matrix[1][1] - $matrix[2][1] * $matrix[1][2]) -
                      $matrix[1][0] * ($matrix[2][2] * $matrix[0][1] - $matrix[2][1] * $matrix[0][2]) +
                      $matrix[2][0] * ($matrix[1][2] * $matrix[0][1] - $matrix[1][1] * $matrix[0][2]));

        return [
            [  $scale * ($matrix[2][2] * $matrix[1][1] - $matrix[2][1] * $matrix[1][2]),
              -$scale * ($matrix[2][2] * $matrix[0][1] - $matrix[2][1] * $matrix[0][2]),
               $scale * ($matrix[1][2] * $matrix[0][1] - $matrix[1][1] * $matrix[0][2]) ],

            [ -$scale * ($matrix[2][2] * $matrix[1][0] - $matrix[2][0] * $matrix[1][2]),
               $scale * ($matrix[2][2] * $matrix[0][0] - $matrix[2][0] * $matrix[0][2]),
              -$scale * ($matrix[1][2] * $matrix[0][0] - $matrix[1][0] * $matrix[0][2]) ],

            [  $scale * ($matrix[2][1] * $matrix[1][0] - $matrix[2][0] * $matrix[1][1]),
              -$scale * ($matrix[2][1] * $matrix[0][0] - $matrix[2][0] * $matrix[0][1]),
               $scale * ($matrix[1][1] * $matrix[0][0] - $matrix[1][0] * $matrix[0][1]) ]
        ];
    }

    public static function multiply3x3Matrix(array $matrixA, array $matrixB): array {
        return [
            [ $matrixA[0][0] * $matrixB[0][0] + $matrixA[0][1] * $matrixB[1][0] + $matrixA[0][2] * $matrixB[2][0],
              $matrixA[0][0] * $matrixB[0][1] + $matrixA[0][1] * $matrixB[1][1] + $matrixA[0][2] * $matrixB[2][1],
              $matrixA[0][0] * $matrixB[0][2] + $matrixA[0][1] * $matrixB[1][2] + $matrixA[0][2] * $matrixB[2][2] ],
            [ $matrixA[1][0] * $matrixB[0][0] + $matrixA[1][1] * $matrixB[1][0] + $matrixA[1][2] * $matrixB[2][0],
              $matrixA[1][0] * $matrixB[0][1] + $matrixA[1][1] * $matrixB[1][1] + $matrixA[1][2] * $matrixB[2][1],
              $matrixA[1][0] * $matrixB[0][2] + $matrixA[1][1] * $matrixB[1][2] + $matrixA[1][2] * $matrixB[2][2] ],
            [ $matrixA[2][0] * $matrixB[0][0] + $matrixA[2][1] * $matrixB[1][0] + $matrixA[2][2] * $matrixB[2][0],
              $matrixA[2][0] * $matrixB[0][1] + $matrixA[2][1] * $matrixB[1][1] + $matrixA[2][2] * $matrixB[2][1],
              $matrixA[2][0] * $matrixB[0][2] + $matrixA[2][1] * $matrixB[1][2] + $matrixA[2][2] * $matrixB[2][2] ]
        ];
    }

    public static function multiply3x3MatrixVector(array $matrixA, array $vectorB) : array {
        return [
            $matrixA[0][0] * $vectorB[0] + $matrixA[0][1] * $vectorB[1] + $matrixA[0][2] * $vectorB[2],
            $matrixA[1][0] * $vectorB[0] + $matrixA[1][1] * $vectorB[1] + $matrixA[1][2] * $vectorB[2],
            $matrixA[2][0] * $vectorB[0] + $matrixA[2][1] * $vectorB[1] + $matrixA[2][2] * $vectorB[2]
        ];
    }
}
