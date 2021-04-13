<?php
declare(strict_types=1);
namespace dW\Pigmentum\WorkingSpace\RGB;

abstract class AdobeRGB1998 extends \dW\Pigmentum\WorkingSpace\RGB\AbstractRGB {
    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.2100, 0.7100 ],
        [ 0.1500, 0.0600 ]
    ];
}