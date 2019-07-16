<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class RGB extends AbstractSpace {
    protected $_r;
    protected $_g;
    protected $_b;

    protected $_workingSpace;

    public function __construct(float $r, float $g, float $b, string $workingSpace = null) {
        if (is_null($workingSpace)) {
            $workingSpace = \dW\Pigmentum\Color::$workingSpace;
        }

        $this->_r = $r;
        $this->_g = $g;
        $this->_b = $b;
        $this->_workingSpace = $workingSpace;
    }


    public function __get($property) {
        if ($property === 'workingSpace') {
            return $this->_workingSpace;
        }
    }
}