<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

abstract class ColorSpace {
    public function __get(string $property) {
        $prop = "_$property";
        if (property_exists($this, $prop)) {
            if ($this->$prop === null) {
                $method = "to$property";
                $this->$prop = $this->$method();
            }

            return $this->$prop;
        }
    }
}