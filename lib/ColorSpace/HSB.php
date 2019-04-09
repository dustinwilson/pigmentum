<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class HSB extends AbstractSpace {
    protected $_h;
    protected $_s;
    protected $_b;

    protected $_hex;
    protected $_workingSpace;

    public function __construct(float $h, float $s, float $b, string $workingSpace = dW\Pigmentum\Color::WORKING_SPACE_RGB_sRGB) {
        $this->_h = $h;
        $this->_s = $s;
        $this->_b = $b;
        $this->_workingSpace = $workingSpace;
    }
}