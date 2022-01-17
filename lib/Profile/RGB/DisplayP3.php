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

    protected static array $xyzMatrix = [
        [ 0.48663265, 0.2656631625, 0.1981741875 ],
        [ 0.2290036, 0.6917267249999999, 0.079269675 ],
        [ -3.972579210032023E-17, 0.04511261250000004, 1.0437173875 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 2.4931807553289667, -0.9312655254971397, -0.40265972375888165 ],
        [ -0.8295031158210787, 1.7626941211197922, 0.02362508874173958 ],
        [ 0.03585362578007169, -0.07618895478265217, 0.9570926215180212 ]
    ];


    // Display P3 uses sRGB's companding methods
    public static function companding(float $channel): float {
        return ($channel <= 0.0031308) ? 12.92 * $channel : 1.055 * $channel ** (1 / 2.4) - 0.055;
    }

    public static function inverseCompanding(float $channel): float {
        return ($channel <= 0.04045) ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}