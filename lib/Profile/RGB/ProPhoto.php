<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile\RGB;

class ProPhoto extends \dW\Pigmentum\Profile\RGB {
    const name = 'ProPhoto RGB';

    const illuminant = Color::ILLUMINANT_D50;

    const chromaticity = [
        [ 0.7347, 0.2653 ],
        [ 0.1596, 0.8404 ],
        [ 0.0366, 0.0001 ]
    ];

    const gamma = 1.8;
}