<?php
declare(strict_types=1);
namespace dW\Pigmentum\ColorSpace;
use dW\Pigmentum\Color as Color;
use dW\Pigmentum\Profile\RGB as Profile;
use dW\Pigmentum\ColorSpace\{
    RGB\HSB as ColorSpaceHSB,
    XYZ as ColorSpaceXYZ
};

class RGB extends ColorSpace implements \Stringable {
    protected float $_R;
    protected float $_G;
    protected float $_B;
    protected float $_unclampedR;
    protected float $_unclampedG;
    protected float $_unclampedB;
    protected string $_profile;
    protected bool $_outOfGamut = false;

    // Child color spaces
    protected ?string $_Hex = null;
    protected ?ColorSpaceHSB $_HSB = null;

    // Reference to XYZ values used when converting color profiles
    protected ColorSpaceXYZ $xyz;


    public function __construct(float $R, float $G, float $B, float $unclampedR, float $unclampedG, float $unclampedB, ?string $profile = null, ColorSpaceXYZ $xyz = null, ?string $hex = null, ?ColorSpaceHSB $HSB = null, bool $outOfGamut = false) {
        $profile = self::validateProfile($profile);

        $this->_R = $R;
        $this->_G = $G;
        $this->_B = $B;
        $this->_unclampedR = $unclampedR;
        $this->_unclampedG = $unclampedG;
        $this->_unclampedB = $unclampedB;
        $this->_profile = ($profile !== null) ? $profile : Color::$workingSpaceRGB;
        $this->_outOfGamut = $outOfGamut;
        $this->xyz = clone $xyz;

        if ($hex !== null) {
            $this->_Hex = $hex;
        }

        if ($HSB !== null) {
            $this->_HSB = $HSB;
        }
    }


    public function changeProfile(?string $profile = null): self {
        $profile = self::validateProfile($profile);
        // Nothing to do if the profile is the same.
        if ($profile === $this->profile) {
            return $this;
        }

        $color = Color::withXYZ($this->xyz->X, $this->xyz->Y, $this->xyz->Z);
        $rgb = $color->toRGB($profile);

        $this->_R = $rgb->R;
        $this->_G = $rgb->G;
        $this->_B = $rgb->B;
        $this->_unclampedR = $rgb->unclampedR;
        $this->_unclampedG = $rgb->unclampedG;
        $this->_unclampedB = $rgb->unclampedB;
        $this->_profile = $profile;
        $this->_outOfGamut = $rgb->outOfGamut;

        if ($this->_Hex !== null) {
            $this->toHex();
        }

        if ($this->_HSB !== null) {
            $this->toHSB();
        }

        return $this;
    }

    public function toHex(): string {
        $this->_Hex = sprintf("#%02x%02x%02x", (int)round($this->_R), (int)round($this->_G), (int)round($this->_B));
        return $this->_Hex;
    }

    public function toHSB(): ColorSpaceHSB {
        $r = $this->R / 255;
        $g = $this->G / 255;
        $b = $this->B / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $d = $max - $min;
        $v = $max;

        if ($d == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $d / $max;

            $R = ((($max - $r) / 6) + ($d / 2)) / $d;
            $G = ((($max - $g) / 6) + ($d / 2)) / $d;
            $B = ((($max - $b) / 6) + ($d / 2)) / $d;

            if ($r == $max) {
                $h = $B - $G;
            } elseif ($g == $max) {
                $h = (1 / 3) + $R - $B;
            } elseif ($b == $max) {
                $h = (2 / 3) + $G - $R;
            }

            if ($h < 0) {
                $h += 1;
            }
            if ($h > 1) {
                $h -= 1;
            }
        }

        $this->_HSB = new ColorSpaceHSB($h * 360, $s * 100, $v * 100);
        return $this->_HSB;
    }

    public static function validateProfile(?string $profile = null): string {
        if ($profile !== null) {
            // This whole process of passing around profiles as strings is stupid as
            // evidenced by the line below, but PHP has no way of handling passing around
            // static classes yet.
            if (!in_array(Profile::class, class_parents($profile))) {
                throw new \Exception("$profile is not an instance of " . Profile::class . ".\n");
            }

            return $profile;
        }

        return Color::$workingSpaceRGB;
    }


    public function __toString(): string {
        return "rgb({$this->_R}, {$this->_G}, {$this->_B})";
    }
}