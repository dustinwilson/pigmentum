<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\Color as Color;
use \dW\Pigmentum\Color\Profile\RGB as Profile;

class RGB extends ColorSpace {
    protected $_R;
    protected $_G;
    protected $_B;

    protected $_profile;

    public function __construct(float $R, float $G, float $B, int $profile = -1) {
        $this->_R = $R;
        $this->_G = $G;
        $this->_B = $B;
        $this->_profile = $profile;
    }
}