<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile;

abstract class Profile {
    const illuminant = \dW\Pigmentum\Color::ILLUMINANT_D65;
    const chromaticity = [];
    const gamma = 1;
}