<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile\RGB;
use dW\Pigmentum\Color as Color;

class ProPhoto extends \dW\Pigmentum\Profile\RGB {
    const name = 'ProPhoto RGB';

    const illuminant = Color::ILLUMINANT_D50;

    const chromaticity = [
        [ 0.7347, 0.2653 ],
        [ 0.1596, 0.8404 ],
        [ 0.0366, 0.0001 ]
    ];

    const gamma = 1.8;


    protected static array $xyzMatrix = [
        [ 0.7976749444306044, 0.13519170147409815, 0.031353354095297416 ],
        [ 0.2880402378623102, 0.7118740972357901, 8.566490189971971E-5 ],
        [ 0, 0, 0.82521 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 1.3459433009386654, -0.25560750931676696, -0.05111176587088495 ],
        [ -0.544598869458717, 1.508167317720767, 0.020535141586646915 ],
        [ 0, 0, 1.2118127506937628 ]
    ];
}