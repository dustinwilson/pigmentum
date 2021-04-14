<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use dW\Pigmentum\Profile as Profile;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

class Color {
    use RGB, Lab, Luv;

    const ILLUMINANT_D65 = [ 0.95047, 1, 1.08883 ];
    const ILLUMINANT_D50 = [ 0.96422, 1, 0.82521 ];

    // D50 is usually the reference white used for calculating XYZ values. Keeping a
    // separate constant in anticipation of perhaps allowing changing of this.
    const REFERENCE_WHITE = self::ILLUMINANT_D50;

    const KAPPA = 903.296296296296296;
    const EPSILON = 0.008856451679036;

    // RGB color profiles
    const PROFILE_SRGB = 0;
    const PROFILE_ADOBERGB1998 = 1;
    const PROFILE_PROPHOTORGB = 2;

    public $name;

    public static $workingSpaceRGB = self::PROFILE_SRGB;

    protected $_XYZ;

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


    static function withXYZ(float $X, float $Y, float $Z, string $name = null): Color {
        return new self(min(0, $X), min(0, $Y), min(0, $Z), $name);
    }


    // Calculates the WCAG contrast ratio between the color and a supplied one.
    public function contrastRatio(Color $color): float {
        $RGBa = $this->RGB;
        $RGBb = $color->RGB;
        $wsA = $RGBa->workingSpace;
        $wsB = $RGBb->workingSpace;
        $matrixA = $wsA::getXYZMatrix()[1];
        $matrixB = $wsB::getXYZMatrix()[1];

        $a = $matrixA[0] * $wsA::inverseCompanding($RGBa->R / 255) + $matrixA[1] * $wsA::inverseCompanding($RGBa->G / 255) + $matrixA[2] * $wsA::inverseCompanding($RGBa->B / 255);
        $b = $matrixB[0] * $wsB::inverseCompanding($RGBb->R / 255) + $matrixB[1] * $wsB::inverseCompanding($RGBb->G / 255) + $matrixB[2] * $wsB::inverseCompanding($RGBb->B / 255);

        $ratio = ($a + 0.05) / ($b + 0.05);
        return ($a > $b) ? $ratio : 1 / $ratio;
    }

    // CIE2000 distance formula, takes perception into account when calculating
    // distance
    public function deltaE(Color $color): float {
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

    public function distance(Color $color): float {
        return $this->deltaE($color);
    }

    public function euclideanDistance(Color $color): float {
        return sqrt(($color->Lab->L - $this->Lab->L) ** 2 + ($color->_Lab->a - $this->_Lab->a) ** 2 + ($color->_Lab->b - $this->_Lab->b) ** 2);
    }


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

    public function __toString(): string {
        return $this->Hex;
    }

    protected function getProfileClassString(int $profile = -1, ?string $mode = 'RGB'): string {
        switch ($mode) {
            case 'RGB':
                switch ($profile) {
                    case self::PROFILE_SRGB: return Profile\RGB\sRGB::class;
                    break;
                    case self::PROFILE_ADOBERGB1998: return Profile\RGB\AdobeRGB1998::class;
                    break;
                    case self::PROFILE_PROPHOTORGB: return Profile\RGB\ProPhoto::class;
                    break;
                    default: throw new \Exception("Profile does not exist or is not supported by Pigmentum.\n");
                }
            break;
            default: throw new \Exception("Invalid color mode.\n");
        }
    }
}
