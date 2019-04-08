<?php
declare(strict_types=1);
namespace dW\Pigmentum\WorkingSpace\RGB;

abstract class AdobeRGB1998 extends \dW\Pigmentum\WorkingSpace\AbstractRGB {
    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.3000, 0.6000 ],
        [ 0.1500, 0.0600 ]
    ];
}