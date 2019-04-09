<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class RGB extends AbstractSpace {
    protected $_r;
    protected $_g;
    protected $_b;
    protected $_workingSpace;

    public function __construct(int $r, int $g, int $b, string $workingSpace = dW\Pigmentum\Color::WORKING_SPACE_RGB_sRGB) {
        $this->_r = $r;
        $this->_g = $g;
        $this->_b = $b;
        $this->_workingSpace = $workingSpace;
    }
}