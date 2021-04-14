<?php
declare(strict_types=1);
namespace dW\Pigmentum\WorkingSpace;

abstract class WorkingSpace {
    const illuminant = \dW\Pigmentum\Color::ILLUMINANT_D65;
    const chromaticity = [];
    const gamma = 1;
}