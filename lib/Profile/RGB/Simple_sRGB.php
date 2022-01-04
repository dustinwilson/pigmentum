<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile\RGB;

class Simple_sRGB extends \dW\Pigmentum\Profile\RGB {
    const name = 'Simple sRGB';

    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.3000, 0.6000 ],
        [ 0.1500, 0.0600 ]
    ];
}
