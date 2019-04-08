<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

class LMS extends AbstractSpace {
    protected $_rho;
    protected $_gamma;
    protected $_beta;

    public function __construct(float $rho, float $gamma, float $beta) {
        $this->_rho = $rho;
        $this->_gamma = $gamma;
        $this->_beta = $beta;
    }
}