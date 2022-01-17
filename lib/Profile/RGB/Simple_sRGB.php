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

    protected static array $xyzMatrix = [
        [ 0.4124564390896922, 0.357576077643909, 0.18043748326639894 ],
        [ 0.21267285140562253, 0.715152155287818, 0.07217499330655958 ],
        [ 0.0193338955823293, 0.11919202588130297, 0.9503040785363679 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 3.2404541621141045, -1.5371385127977166, -0.498531409556016 ],
        [ -0.9692660305051868, 1.8760108454466942, 0.041556017530349834 ],
        [ 0.055643430959114726, -0.2040259135167538, 1.0572251882231791 ]
    ];
}
