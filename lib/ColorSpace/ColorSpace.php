<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;

abstract class ColorSpace {
    public function __get($name) {
        $prop = "_$name";
        if (!property_exists($this, $prop)) {
            $trace = debug_backtrace();
            set_error_handler(function($errno, $errstr) use($trace) {
                echo "PHP Notice:  $errstr in {$trace[0]['file']} on line {$trace[0]['line']}" . PHP_EOL;
            });
            trigger_error("Cannot get undefined property $name", \E_USER_NOTICE);
            restore_error_handler();
            return null;
        }

        if ($this->$prop === null) {
            $method = "to$name";
            $this->$prop = $this->$method();
        }

        return $this->$prop;
    }
}