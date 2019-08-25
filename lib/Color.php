<?php
declare(strict_types=1);
namespace dW\Pigmentum;
use MathPHP\LinearAlgebra\Matrix as Matrix;
use MathPHP\LinearAlgebra\Vector as Vector;

class Color {
    use Traits\RGB, Traits\Lab;

    const WS_sRGB = '\dW\Pigmentum\WorkingSpace\RGB\sRGB';
    const WS_ADOBERGB1998 = '\dW\Pigmentum\WorkingSpace\RGB\AdobeRGB1998';

    const ILLUMINANT_D65 = [ 0.95047, 1, 1.08883 ];
    const ILLUMINANT_D50 = [ 0.96422, 1, 0.82521 ];

    const KAPPA = 903.296296296296296;
    const EPSILON = 0.008856451679036;

    protected $_XYZ;

    private function __construct(float $x, float $y, float $z, array $props = []) {
        $this->_XYZ = new ColorSpace\XYZ($x, $y, $z);

        if ($props !== []) {
            foreach ($props as $key => $value) {
                $key = "_$key";
                $this->$key = $value;
            }
        }
    }


    static function withXYZ(float $x, float $y, float $z): Color {
        // Can in some instances have values > 1. Illuminants are such an example.
        $x = ($x < 0.0) ? 0.0 : $x;
        $y = ($y < 0.0) ? 0.0 : $y;
        $z = ($z < 0.0) ? 0.0 : $z;

        return new self($x, $y, $z);
    }


    // Average with RGB.
    public static function average(Color ...$colors): Color {
        $rsum = 0;
        $gsum = 0;
        $bsum = 0;
        $length = sizeof($colors);
        foreach ($colors as $c) {
            $rsum += $c->RGB->r;
            $gsum += $c->RGB->g;
            $bsum += $c->RGB->b;
        }

        return Color::withRGB($rsum / $length, $gsum / $length, $bsum / $length);
    }

    // Calculates the WCAG contrast ratio between the color and a supplied one.
    public function contrastRatio(Color $color): float {
        $RGBa = $this->RGB;
        $RGBb = $color->RGB;
        $wsA = $RGBa->workingSpace;
        $wsB = $RGBb->workingSpace;
        $matrixA = $wsA::getXYZMatrix()[1];
        $matrixB = $wsB::getXYZMatrix()[1];

        $a = $matrixA[0] * $wsA::inverseCompanding($RGBa->r / 255) + $matrixA[1] * $wsA::inverseCompanding($RGBa->g / 255) + $matrixA[2] * $wsA::inverseCompanding($RGBa->b / 255);
        $b = $matrixB[0] * $wsB::inverseCompanding($RGBb->r / 255) + $matrixB[1] * $wsB::inverseCompanding($RGBb->g / 255) + $matrixB[2] * $wsB::inverseCompanding($RGBb->b / 255);

        $ratio = ($a + 0.05) / ($b + 0.05);
        return ($a > $b) ? $ratio : 1 / $ratio;
    }

    // Mix with L*a*b*. Colors in this color space are perceptively uniform and are
    // perfect for mixing.
    public function mix(Color $color, float $percentage = 0.5): Color {
        if ($percentage == 0) {
            return $this;
        } elseif ($percentage == 1) {
            return $color;
        }

        $distance = $this->distance($color);
        $travelDistance = $distance - ($distance * ((100 - $percentage *= 100) / 100));
        $ratio = $travelDistance / $distance;
        return Color::withLab(
            $this->Lab->L + ($ratio * ($color->Lab->L - $this->Lab->L)),
            $this->Lab->a + ($ratio * ($color->Lab->a - $this->Lab->a)),
            $this->Lab->b + ($ratio * ($color->Lab->b - $this->Lab->b))
        );
    }

    // Euclidean distance
    public function distance(Color $color): float {
        return sqrt(($color->Lab->L - $this->Lab->L) ** 2 + ($color->_Lab->a - $this->_Lab->a) ** 2 + ($color->_Lab->b - $this->_Lab->b) ** 2);
    }

    public function difference(Color $color): float {
        return $this->deltaE($color);
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
}
