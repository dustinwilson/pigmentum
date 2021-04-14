<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile\RGB;

abstract class sRGB extends \dW\Pigmentum\Profile\RGB {
    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.3000, 0.6000 ],
        [ 0.1500, 0.0600 ]
    ];

    // sRGB has its own companding methods
    public static function companding(float $channel): float {
        return ($channel <= 0.0031308) ? 12.92 * $channel : 1.055 * $channel ** (1 / 2.4) - 0.055;
    }

    public static function inverseCompanding(float $channel): float {
        return ($channel <= 0.04045) ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
