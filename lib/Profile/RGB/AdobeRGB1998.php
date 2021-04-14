<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile\RGB;

abstract class AdobeRGB1998 extends \dW\Pigmentum\Profile\RGB {
    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.2100, 0.7100 ],
        [ 0.1500, 0.0600 ]
    ];
}