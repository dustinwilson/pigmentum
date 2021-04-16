<?php
declare(strict_types=1);
namespace dW\Pigmentum\Profile;
use dW\Pigmentum\Color as Color;

abstract class Profile {
    const illuminant = Color::ILLUMINANT_D65;
    const chromaticity = [];
    const gamma = 1;
    const name = '';
}