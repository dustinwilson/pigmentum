<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace\XYZ;

class LMS extends \dW\Pigmentum\ColorSpace\ColorSpace implements \Stringable {
    protected float $_rho;
    protected float $_gamma;
    protected float $_beta;

    public function __construct(float $rho, float $gamma, float $beta) {
        $this->_rho = $rho;
        $this->_gamma = $gamma;
        $this->_beta = $beta;
    }

    public function __toString() {
        return "lms({$this->_rho}, {$this->_gamma}, {$this->_beta})";
    }
}