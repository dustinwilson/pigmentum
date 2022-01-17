<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use dW\Pigmentum\Profile as Profile;
use dW\Pigmentum\ColorSpace\XYZ as ColorSpaceXYZ;

class Color {
    use Lab, RGB;

    // Common illuminants used in color spaces
    const ILLUMINANT_D65 = [ 0.95047, 1, 1.08883 ];
    const ILLUMINANT_D50 = [ 0.96422, 1, 0.82521 ];

    // D50 is usually the reference white used for calculating XYZ values. Keeping a
    // separate constant in anticipation of perhaps allowing changing of this.
    const REFERENCE_WHITE = self::ILLUMINANT_D50;

    // Math constants
    const KAPPA = 903.296296296296296;
    const EPSILON = 0.008856451679036;

    // RGB color profiles
    const PROFILE_SRGB = __NAMESPACE__ . '\Profile\RGB\sRGB';
    const PROFILE_SIMPLE_SRGB = __NAMESPACE__ . '\Profile\RGB\Simple_sRGB';
    const PROFILE_ADOBERGB1998 = __NAMESPACE__ . '\Profile\RGB\AdobeRGB1998';
    const PROFILE_PROPHOTORGB = __NAMESPACE__ . '\Profile\RGB\ProPhoto';
    const PROFILE_DISPLAYP3 = __NAMESPACE__ . '\Profile\RGB\DisplayP3';

    /** A user supplied name for the color */
    public ?string $name = null;
    /** The current RGB working space */
    public static string $workingSpaceRGB = self::PROFILE_SRGB;

    protected ColorSpaceXYZ $_XYZ;


    protected function __construct(float $X, float $Y, float $Z, ?string $name, array $props = []) {
        $this->_XYZ = new ColorSpace\XYZ($X, $Y, $Z);
        $this->name = $name;

        if ($props !== []) {
            foreach ($props as $key => $value) {
                $key = "_$key";
                $this->$key = $value;
            }
        }
    }


    public static function withXYZ(float $X, float $Y, float $Z, string $name = null): self {
        return new self(max(0, $X), max(0, $Y), max(0, $Z), $name);
    }


    public function toXYZ(): ColorSpaceXYZ {
        return $this->_XYZ;
    }


    // APCA contrast between $this (text color) and a supplied background color
    public function apcaContrast(self $backgroundColor): float {
        // APCA's current algorithm mostly uses sRGB, but there is support internally
        // for Adobe RGB and Display P3 as well. However, the algorithm produces
        // different contrast results when colors converted between profiles are
        // compared when mathematically that should not be the case. Going to just
        // convert to sRGB and keep unclamped values.

        // APCA's algorithm uses a weird XYZ D65 color space. I could use chromatic
        // adaptation to convert from D50 to D65 normally, but the APCA algorithm
        // requires multiplying each channel by an odd gamma value when inverse
        // companding. It's 2.4 rather than 2.2 for sRGB and Display P3, but 2.35 for
        // Adobe RGB for some weird reason.

        $sRGBYVector = self::PROFILE_SRGB::getXYZMatrix()[1];
        $gamma = 2.4;

        $txt = $this->toRGB(self::PROFILE_SRGB);
        $bg = $backgroundColor->toRGB(self::PROFILE_SRGB);
        $txtY = $sRGBYVector[0] * ((abs($txt->unclampedR) / 255) ** $gamma) + $sRGBYVector[1] * ((abs($txt->unclampedG) / 255) ** $gamma) + $sRGBYVector[2] * ((abs($txt->unclampedB) / 255) ** $gamma);
        $bgY = $sRGBYVector[0] * ((abs($bg->unclampedR) / 255) ** $gamma) + $sRGBYVector[1] * ((abs($bg->unclampedG) / 255) ** $gamma) + $sRGBYVector[2] * ((abs($bg->unclampedB) / 255) ** $gamma);

        $txtY = ($txtY > 0.022) ? $txtY : $txtY + ((0.022 - $txtY) ** 1.414);
        $bgY = ($bgY > 0.022) ? $bgY : $bgY + ((0.022 - $bgY) ** 1.414);
        if (abs($bgY - $txtY) < 0.0005) {
            return 0;
        }

        // For normal polarity, black text on white
        if ($bgY > $txtY) {
            $SAPC = ($bgY ** 0.56 - $txtY ** 0.57) * 1.14;
            // Low Contrast smooth rollout to prevent polarity reversal and also a low-clip
            // for very low contrasts
            $output = ($SAPC < 0.1) ? 0 : $SAPC - 0.027;
        }
        // For inverse polarity, white text on black
        else {
            $SAPC = ($bgY ** 0.65 - $txtY ** 0.62) * 1.14;
            $output = ($SAPC > -0.1) ? 0 : $SAPC + 0.027;
        }

        return $output * 100;
    }

    public function deltaE(self $color): float {
        $Lab1 = $this->Lab;
        $Lab2 = $color->Lab;

        $kL = 1.0;
	    $kC = 1.0;
	    $kH = 1.0;
	    $lBarPrime = 0.5 * ($Lab1->L + $Lab2->L);
	    $c1 = sqrt($Lab1->a ** 2 + $Lab1->b ** 2);
	    $c2 = sqrt($Lab2->a ** 2 + $Lab2->b ** 2);
	    $cBar = 0.5 * ($c1 + $c2);
	    $cBar7 = $cBar ** 7;
	    $g = 0.5 * (1.0 - sqrt($cBar7 / ($cBar7 + (25 ** 7))));
	    $a1Prime = $Lab1->a * (1.0 + $g);
	    $a2Prime = $Lab2->a * (1.0 + $g);
	    $c1Prime = sqrt($a1Prime ** 2 + $Lab1->b ** 2);
	    $c2Prime = sqrt($a2Prime ** 2 + $Lab2->b ** 2);
	    $cBarPrime = 0.5 * ($c1Prime + $c2Prime);

	    $h1Prime = (atan2($Lab1->b, $a1Prime) * 180.0) / M_PI;
        if ($h1Prime < 0.0) {
            $h1Prime += 360.0;
        }

	    $h2Prime = (atan2($Lab2->b, $a2Prime) * 180.0) / M_PI;
	    if ($h2Prime < 0.0) {
            $h2Prime += 360.0;
        }

	    $hBarPrime = (abs($h1Prime - $h2Prime) > 180.0) ? (0.5 * ($h1Prime + $h2Prime + 360.0)) : (0.5 * ($h1Prime + $h2Prime));
	    $t = 1.0 - 0.17 * cos(M_PI * ($hBarPrime - 30.0) / 180.0) + 0.24 * cos(M_PI * (2.0 * $hBarPrime) / 180.0) + 0.32 * cos(M_PI * (3.0 * $hBarPrime +  6.0) / 180.0) - 0.20 * cos(M_PI * (4.0 * $hBarPrime - 63.0) / 180.0);

        if (abs($h2Prime - $h1Prime) <= 180.0) {
            $dhPrime = $h2Prime - $h1Prime;
        } else {
            $dhPrime = ($h2Prime <= $h1Prime) ? ($h2Prime - $h1Prime + 360.0) : ($h2Prime - $h1Prime - 360.0);
        }

	    $dLPrime = $Lab2->L - $Lab1->L;
	    $dCPrime = $c2Prime - $c1Prime;
	    $dHPrime = 2.0 * sqrt($c1Prime * $c2Prime) * sin(M_PI * (0.5 * $dhPrime) / 180.0);
	    $sL = 1.0 + ((0.015 * ($lBarPrime - 50.0) * ($lBarPrime - 50.0)) / sqrt(20.0 + ($lBarPrime - 50.0) * ($lBarPrime - 50.0)));
	    $sC = 1.0 + 0.045 * $cBarPrime;
	    $sH = 1.0 + 0.015 * $cBarPrime * $t;
	    $dTheta = 30.0 * exp(0 - (($hBarPrime - 275.0) / 25.0) * (($hBarPrime - 275.0) / 25.0));
        $cBarPrime7 = $cBarPrime ** 7;
	    $rC = sqrt($cBarPrime7 / ($cBarPrime7 + (25 ** 7)));
	    $rT = -2.0 * $rC * sin(M_PI * (2.0 * $dTheta) / 180.0);

        return sqrt(
			($dLPrime / ($kL * $sL)) * ($dLPrime / ($kL * $sL)) +
			($dCPrime / ($kC * $sC)) * ($dCPrime / ($kC * $sC)) +
			($dHPrime / ($kH * $sH)) * ($dHPrime / ($kH * $sH)) +
			($dCPrime / ($kC * $sC)) * ($dHPrime / ($kH * $sH)) * $rT
        );
    }

    public function distance(self $color): float {
        return $this->deltaE($color);
    }

    public function euclideanDistance(self $color): float {
        return sqrt(($color->Lab->L - $this->Lab->L) ** 2 + ($color->_Lab->a - $this->_Lab->a) ** 2 + ($color->_Lab->b - $this->_Lab->b) ** 2);
    }

    // Calculates the WCAG2 contrast ratio between the color and a supplied one.
    public function wcag2Contrast(self $color): float {
        $RGBa = $this->RGB;
        $RGBb = $color->RGB;
        $wsA = $RGBa->profile;
        $wsB = $RGBb->profile;
        $matrixA = $wsA::getXYZMatrix()[1];
        $matrixB = $wsB::getXYZMatrix()[1];

        $a = $matrixA[0] * $wsA::inverseCompanding($RGBa->R / 255) + $matrixA[1] * $wsA::inverseCompanding($RGBa->G / 255) + $matrixA[2] * $wsA::inverseCompanding($RGBa->B / 255);
        $b = $matrixB[0] * $wsB::inverseCompanding($RGBb->R / 255) + $matrixB[1] * $wsB::inverseCompanding($RGBb->G / 255) + $matrixB[2] * $wsB::inverseCompanding($RGBb->B / 255);

        $ratio = ($a + 0.05) / ($b + 0.05);
        return ($a > $b) ? $ratio : 1 / $ratio;
    }


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
