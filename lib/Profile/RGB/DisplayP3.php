<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile\RGB;
use dW\Pigmentum\Color as Color;

class DisplayP3 extends \dW\Pigmentum\Profile\RGB {
    const name = 'Display P3';

    const chromaticity = [
        [ 0.680, 0.320 ],
        [ 0.265, 0.690 ],
        [ 0.150, 0.060 ]
    ];

    // Display P3 uses sRGB's companding methods
    public static function companding(float $channel): float {
        return ($channel <= 0.0031308) ? 12.92 * $channel : 1.055 * $channel ** (1 / 2.4) - 0.055;
    }

    public static function inverseCompanding(float $channel): float {
        return ($channel <= 0.04045) ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}