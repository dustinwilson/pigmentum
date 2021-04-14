<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

abstract class ColorSpace {
    public function __get($property) {
        $prop = "_$property";
        if (property_exists($this, $prop)) {
            if (is_null($this->$prop)) {
                $method = "to$property";
                $this->$prop = $this->$method();
            }

            return $this->$prop;
        }
    }
}