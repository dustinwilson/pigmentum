<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

abstract class AbstractSpace {
    public function __get($property) {
        $prop = "_$property";
        if (property_exists($this, $prop)) {
            return $this->$prop;
        }
    }
}