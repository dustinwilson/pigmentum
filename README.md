Pigmentum
=========

[a]: https://en.wikipedia.org/wiki/CIE_1931_color_space
[b]: https://en.wikipedia.org/wiki/CIELAB_color_space
[c]: https://en.wikipedia.org/wiki/CIE_1931_color_space
[d]: https://en.wikipedia.org/wiki/HSL_and_HSV
[e]: https://en.wikipedia.org/wiki/LMS_color_space
[f]: https://en.wikipedia.org/wiki/RGB_color_space
[g]: https://github.com/Myndex/apca-w3
[h]: https://www.php.net/manual/en/book.dom.php
[i]: https://www.php.net/manual/en/book.mbstring.php
[j]: https://www.php.net/manual/en/book.zip.php

Library for manipulating color in PHP. This is the result of my own experiments with color math. There are other color classes out there, but they either work not how I'd like or the math is incorrect.

## Warning Before Using ##

This library is experimental. The code does not have any unit tests yet, but that work is planned. Until unit tests exist, treat this software as beta or even alpha software. Also, the public API is in flux, so if you do use this library you're forewarned of possible breaking API changes.

## Requirements ##

* PHP 8.0.2 or newer with the following extensions:
  - [dom][h] extension (for palettes)
  - [mbstring][i] extension (for palettes)
  - [zip][j] extension (for palettes)
* Composer 2.0 or newer

## Documentation ##

Color in Pigmentum is represented as a single color object. All color spaces are converted to and representated as [XYZ][a] D50 2° internally. Currently Pigmentum handles the following color spaces:

1. [CIEXYZ][c]
    1. [LMS][e]
2. [CIELAB][b]
3. [RGB][f]
    1. [HSB/V][d]

Any color space supported in Pigmentum means that conversions to and from each color space is possible.

### dW\Pigmentum\Color ###

```php
class dW\Pigmentum\Color {
    // Common illuminants used in color spaces
    const ILLUMINANT_D65 = [ 0.95047, 1, 1.08883 ];
    const ILLUMINANT_D50 = [ 0.96422, 1, 0.82521 ];

    const REFERENCE_WHITE = self::ILLUMINANT_D50;

    // Math constants
    const KAPPA = 903.296296296296296;
    const EPSILON = 0.008856451679036;

    // RGB color profiles
    const PROFILE_SRGB = 'dW\Pigmentum\Color\Profile\RGB\sRGB';
    const PROFILE_SIMPLE_SRGB = 'dW\Pigmentum\Color\Profile\RGB\Simple_sRGB';
    const PROFILE_ADOBERGB1998 = 'dW\Pigmentum\Color\Profile\RGB\AdobeRGB1998';
    const PROFILE_PROPHOTORGB = 'dW\Pigmentum\Color\Profile\RGB\ProPhoto';
    const PROFILE_DISPLAYP3 = 'dW\Pigmentum\Color\Profile\RGB\DisplayP3';

    public ?string $name = null;
    public static string $workingSpaceRGB = self::PROFILE_SRGB;


    public static function withLab(float $L, float $a, float $b, ?string $name = null): dW\Pigmentum\Color;
    public static function withLCHab(float $L, float $C, float $H, ?string $name = null): dW\Pigmentum\Color;
    public static function withRGB(float $R, float $G, float $B, ?string $name = null, ?string $profile = null): dW\Pigmentum\Color;
    public static function withRGBHex(string $hex, ?string $name = null, ?string $profile = null): dW\Pigmentum\Color;
    public static function withHSB(float $H, float $S, float $B, ?string $name = null, ?string $profile = null): dW\Pigmentum\Color;
    public static function withXYZ(float $X, float $Y, float $Z, string $name = null): dW\Pigmentum\Color;


    public function toLab(): dW\Pigmentum\ColorSpace\Lab;
    public function toRGB(?string $profile = null): dW\Pigmentum\ColorSpace\RGB;
    public function toXYZ(): dW\Pigmentum\ColorSpace\XYZ;


    public static function average(dW\Pigmentum\Color ...$colors): dW\Pigmentum\Color;
    public static function averageWithLab(dW\Pigmentum\Color ...$colors): dW\Pigmentum\Color;
    public static function averageWithLCHab(dW\Pigmentum\Color ...$colors): dW\Pigmentum\Color;
    public static function averageWithRGB(dW\Pigmentum\Color ...$colors): dW\Pigmentum\Color;
    public static function averageWithHSB(dW\Pigmentum\Color ...$colors): dW\Pigmentum\Color;

    public function mix(dW\Pigmentum\Color $color, float $percentage = 0.5): dW\Pigmentum\Color;
    public function mixWithLab(dW\Pigmentum\Color $color, float $percentage = 0.5): dW\Pigmentum\Color;
    public function mixWithLCHab(dW\Pigmentum\Color $color, float $percentage = 0.5): dW\Pigmentum\Color;
    public function mixWithRGB(dW\Pigmentum\Color $color, float $percentage = 0.5): dW\Pigmentum\Color;
    public function mixWithHSB(dW\Pigmentum\Color $color, float $percentage = 0.5): dW\Pigmentum\Color;


    public function apcaContrast(dW\Pigmentum\Color $backgroundColor): float;
    public function deltaE(dW\Pigmentum\Color $color): float;
    public function distance(dW\Pigmentum\Color $color): float;
    public function euclideanDistance(dW\Pigmentum\Color $color): float;
    public function wcag2Contrast(dW\Pigmentum\Color $color): float;
}
```

#### Properties ####

* *name* (?string): A user-supplied name for the color. Useful when making palettes.
* *workingSpaceRGB* (string): The current RGB working space.

---

#### dW\Pigmentum\Color::withLab ####

Creates a new `dW\Pigmentum\Color` object from L\*a\*b* values.

```php
public static function withLab(
    float $L,
    float $a,
    float $b,
    ?string $name = null
): dW\Pigmentum\Color;
```

* `L`: The lightness channel value
* `a`: The a channel value
* `b`: The b channel value
* `name`: An optional name to associate with the color

##### Example #####

```php
namespace dW\Pigmentum\Color;

Color::withLab(57, 35, 38);
```

---

#### dW\Pigmentum\Color::withLCHab ####

Creates a new `dW\Pigmentum\Color` object from L\*C\*H\* (L\*a\*b\*) values.

```php
public static function withLCHab(
    float $L,
    float $C,
    float $H,
    ?string $name = null
): dW\Pigmentum\Color;
```

* `L`: The lightness channel value
* `C`: The chroma channel value
* `H`: The hue channel value
* `name`: An optional name to associate with the color

##### Example #####

```php
namespace dW\Pigmentum\Color;

Color::withLCHab(57, 51, 47);
```

---

#### dW\Pigmentum\Color::withRGB ####

Creates a new `dW\Pigmentum\Color` object from RGB values.

```php
public static function withRGB(
    float $R,
    float $G,
    float $B,
    ?string $name = null,
    ?string $profile = null
): dW\Pigmentum\Color;
```

* `R`: The red channel value
* `G`: The green channel value
* `B`: The blue channel value
* `name`: An optional name to associate with the color
* `profile`: A string representation of the class name of the color profile the channel values are in, defaults to the current working space

##### Example #####

```php
namespace dW\Pigmentum\Color;

$sRGBColor = Color::withRGB(202, 110, 72);
$adobeRGBColor = Color::withRGB(202, 110, 72, null, Color::PROFILE_ADOBERGB1998);
echo $sRGBColor->XYZ . "\n";
echo $adobeRGBColor->XYZ;
```

Outputs:

```
xyz(0.32686782145478, 0.24712385141221, 0.069650535956488)
xyz(0.40672668489701, 0.28866272343639, 0.067355999640287)
```

---

#### dW\Pigmentum\Color::withRGBHex ####

Creates a new `dW\Pigmentum\Color` object from an RGB hex string.

```php
public static function withRGBHex(
    string $hex,
    ?string $name = null,
    ?string $profile = null
): dW\Pigmentum\Color;
```

* `hex`: An RGB hex string; can be preceded by a '#' or without
* `name`: An optional name to associate with the color
* `profile`: A string representation of the class name of the color profile the channel values are in, defaults to the current working space

##### Example #####

```php
namespace dW\Pigmentum\Color;

$sRGBColor = Color::withRGBHex('#ca6e48');
$adobeRGBColor = Color::withRGBHex('#ca6e48', null, Color::PROFILE_ADOBERGB1998);
echo $sRGBColor->XYZ . "\n";
echo $adobeRGBColor->XYZ;
```

Outputs:

```
xyz(0.32686782145478, 0.24712385141221, 0.069650535956488)
xyz(0.40672668489701, 0.28866272343639, 0.067355999640287)
```

---

#### dW\Pigmentum\Color::withHSB ####

Creates a new `dW\Pigmentum\Color` object from RGB values.

```php
public static function withHSB(
    float $H,
    float $S,
    float $B,
    ?string $name = null,
    ?string $profile = null
): dW\Pigmentum\Color;
```

* `H`: The hue channel value
* `S`: The saturation channel value
* `B`: The brightness channel value
* `name`: An optional name to associate with the color
* `profile`: A string representation of the class name of the color profile the channel values are in, defaults to the current working space

##### Example #####

```php
namespace dW\Pigmentum\Color;

Color::withHSB(18, 64, 79);
```

---

#### dW\Pigmentum\Color::withXYZ ####

Creates a new `dW\Pigmentum\Color` object from XYZ values.

```php
public static function withXYZ(
    float $X,
    float $Y,
    float $Z,
    ?string $name = null
): dW\Pigmentum\Color;
```

* `X`: The X channel value
* `Y`: The Y channel value
* `Z`: The Z channel value
* `name`: An optional name to associate with the color

##### Example #####

```php
namespace dW\Pigmentum\Color;

Color::withXYZ(0.3267, 0.2471, 0.0696);
```

---

#### dW\Pigmentum\Color::toLab ####

Returns the L\*a\*b\* color space for the color.

```php
public function toLab(): dW\Pigmentum\ColorSpace\Lab;
```

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withHSB(18, 64, 79);
echo $color->toLab() . "\n";
echo $color->Lab;
```

Outputs:

```
lab(56.977258534337, 34.064915293425, 37.682616197795)
lab(56.977258534337, 34.064915293425, 37.682616197795)
```

---

#### dW\Pigmentum\Color::toRGB ####

Returns the RGB color space for the color.

```php
public function toRGB(
    ?string $profile = null
): dW\Pigmentum\ColorSpace\RGB;
```

* `profile`: A string representation of the class name of the color profile the channel values are in, defaults to the current working space

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withXYZ(0.3267, 0.2471, 0.0696);
echo $color->toRGB() . "\n";
echo $color->RGB;
```

Outputs:

```
rgb(201.92948812963, 110.03872289405, 71.957047956757)
rgb(201.92948812963, 110.03872289405, 71.957047956757)
```

---

#### dW\Pigmentum\Color::toXYZ ####

Returns the XYZ color space for the color.

```php
public function toXYZ(): dW\Pigmentum\ColorSpace\XYZ;
```

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48');
echo $color->toXYZ() . "\n";
echo $color->XYZ;
```

Outputs:

```
xyz(0.32686782145478, 0.24712385141221, 0.069650535956488)
xyz(0.32686782145478, 0.24712385141221, 0.069650535956488)
```

---

#### dW\Pigmentum\Color::average ####

Averages the provided colors in the L\*a\*b\* color space and returns a new Color object. Identical to `dW\Pigmentum\Color::averageWithLab`.

```php
public static function average(
    dW\Pigmentum\Color ...$colors
): dW\Pigmentum\Color;
```

* `colors`: One or more colors to average.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::average(Color::withRGBHex('#ca6e48'), Color::withXYZ(0.0864, 0.0868, 0.1409), Color::withLab(100, 0, 0));
echo $color->RGB->Hex;
```

Outputs:

```
#b49393
```

---

#### dW\Pigmentum\Color::averageWithLab ####

Averages the provided colors in the L\*a\*b\* color space and returns a new Color object. Identical to `dW\Pigmentum\Color::average`.

```php
public static function averageWithLab(
    dW\Pigmentum\Color ...$colors
): dW\Pigmentum\Color;
```

* `colors`: One or more colors to average.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::averageWithLab(Color::withRGBHex('#ca6e48'), Color::withXYZ(0.0864, 0.0868, 0.1409), Color::withLab(100, 0, 0));
echo $color->RGB->Hex;
```

Outputs:

```
#b49393
```

---

#### dW\Pigmentum\Color::averageWithLCHab ####

Averages the provided colors in the LCH (L\*a\*b\*) color space and returns a new Color object.

```php
public static function averageWithLCHab(
    dW\Pigmentum\Color ...$colors
): dW\Pigmentum\Color;
```

* `colors`: One or more colors to average.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::averageWithLCHab(Color::withRGBHex('#ca6e48'), Color::withXYZ(0.0864, 0.0868, 0.1409), Color::withLab(100, 0, 0));
echo $color->RGB->Hex;
```

Outputs:

```
#9a9f71
```

---

#### dW\Pigmentum\Color::averageWithRGB ####

Averages the provided colors in the RGB color space and returns a new Color object.

```php
public static function averageWithRGB(
    dW\Pigmentum\Color ...$colors
): dW\Pigmentum\Color;
```

* `colors`: One or more colors to average.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::averageWithRGB(Color::withRGBHex('#ca6e48'), Color::withXYZ(0.0864, 0.0868, 0.1409), Color::withLab(100, 0, 0));
echo $color->RGB->Hex;
```

Outputs:

```
#b09595
```

---

#### dW\Pigmentum\Color::averageWithHSB ####

Averages the provided colors in the HSB color space and returns a new Color object.

```php
public static function averageWithHSB(
    dW\Pigmentum\Color ...$colors
): dW\Pigmentum\Color;
```

* `colors`: One or more colors to average.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::averageWithHSB(Color::withRGBHex('#ca6e48'), Color::withXYZ(0.0864, 0.0868, 0.1409), Color::withLab(100, 0, 0));
echo $color->RGB->Hex;
```

Outputs:

```
#a9c07c
```

---

#### dW\Pigmentum\Color::mix ####

Mixes the color with a provided color in the L\*a\*b\* color space and returns a new Color object. Identical to `dW\Pigmentum\Color::mixWithLab`.

```php
public function mix(
    dW\Pigmentum\Color $color,
    float $percentage = 0.5
): dW\Pigmentum\Color;
```

* `color`: Color to mix with `$this`.
* `percentage`: How strong to mix the color with `$this`.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48')->mix(Color::withXYZ(0.0864, 0.0868, 0.1409), 0.625);
echo $color->RGB->Hex;
```

Outputs:

```
#7e5e67
```

---

#### dW\Pigmentum\Color::mixWithLab ####

Mixes the color with a provided color in the L\*a\*b\* color space and returns a new Color object. Identical to `dW\Pigmentum\Color::mix`.

```php
public function mixWithLab(
    dW\Pigmentum\Color $color,
    float $percentage = 0.5
): dW\Pigmentum\Color;
```

* `color`: Color to mix with `$this`.
* `percentage`: How strong to mix the color with `$this`.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48')->mixWithLab(Color::withXYZ(0.0864, 0.0868, 0.1409), 0.625);
echo $color->RGB->Hex;
```

Outputs:

```
#7e5e67
```

---

#### dW\Pigmentum\Color::mixWithLCHab ####

Mixes the color with a provided color in the LCH (L\*a\*b\*) color space and returns a new Color object.

```php
public function mixWithLab(
    dW\Pigmentum\Color $color,
    float $percentage = 0.5
): dW\Pigmentum\Color;
```

* `color`: Color to mix with `$this`.
* `percentage`: How strong to mix the color with `$this`.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48')->mixWithLCHab(Color::withXYZ(0.0864, 0.0868, 0.1409), 0.625);
echo $color->RGB->Hex;
```

Outputs:

```
#875587
```

---

#### dW\Pigmentum\Color::mixWithRGB ####

Mixes the color with a provided color in the RGB color space and returns a new Color object.

```php
public function mixWithRGB(
    dW\Pigmentum\Color $color,
    float $percentage = 0.5
): dW\Pigmentum\Color;
```

* `color`: Color to mix with `$this`.
* `percentage`: How strong to mix the color with `$this`.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48')->mixWithRGB(Color::withXYZ(0.0864, 0.0868, 0.1409), 0.625);
echo $color->RGB->Hex;
```

Outputs:

```
#785d65
```

---

#### dW\Pigmentum\Color::mixWithHSB ####

Mixes the color with a provided color in the HSB color space and returns a new Color object.

```php
public function mixWithHSB(
    dW\Pigmentum\Color $color,
    float $percentage = 0.5
): dW\Pigmentum\Color;
```

* `color`: Color to mix with `$this`.
* `percentage`: How strong to mix the color with `$this`.

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48')->mixWithHSB(Color::withXYZ(0.0864, 0.0868, 0.1409), 0.625);
echo $color->RGB->Hex;
```

Outputs:

```
#7f4b96
```

---

#### dW\Pigmentum\Color::apcaContrast ####

Calculate the [APCA][g] (indented for use with the future WCAG 3) contrast between a text color (`$this`) and a provided background color.

**NOTE**: This algorithm is in flux, and its results may change over time as the upstream reference algorithm is updated.

```php
public function apcaContrast(
    dW\Pigmentum\Color $backgroundColor
): dW\Pigmentum\Color;
```

* `backgroundColor`: Color to calculate contrast against.

##### Example #####

```php
namespace dW\Pigmentum\Color;

echo Color::withRGBHex('#ca6e48')->apcaContrast(Color::withXYZ(0.0864, 0.0868, 0.1409));
```

Outputs:

```
-21.758825698145
```

---

#### dW\Pigmentum\Color::deltaE ####

Calculate the CIE2000 distance between `$this` and a supplied color. Identical to `dW\Pigmentum\Color::distance`.

```php
public function deltaE(
    dW\Pigmentum\Color $color
): dW\Pigmentum\Color;
```

* `color`: Color to calculate distance from.

##### Example #####

```php
namespace dW\Pigmentum\Color;

echo Color::withRGBHex('#ca6e48')->deltaE(Color::withXYZ(0.0864, 0.0868, 0.1409));
```

Outputs:

```
41.674529389586
```

---

#### dW\Pigmentum\Color::distance ####

Calculate the CIE2000 distance between `$this` and a supplied color. The CIE2000 distance formula takes perception into account when calculating. Identical to `dW\Pigmentum\Color::deltaE`.

```php
public function distance(
    dW\Pigmentum\Color $color
): dW\Pigmentum\Color;
```

* `color`: Color to calculate distance from.

##### Example #####

```php
namespace dW\Pigmentum\Color;

echo Color::withRGBHex('#ca6e48')->distance(Color::withXYZ(0.0864, 0.0868, 0.1409));
```

Outputs:

```
41.674529389586
```

---

#### dW\Pigmentum\Color::euclideanDistance ####

Calculate the geometric euclidean distance between `$this` and a supplied color. This does not take perception into account when calculating. See `dW\Pigmentum\Color::deltaE` for perceptual distance.

```php
public function euclideanDistance(
    dW\Pigmentum\Color $color
): dW\Pigmentum\Color;
```

* `color`: Color to calculate distance from.

##### Example #####

```php
namespace dW\Pigmentum\Color;

echo Color::withRGBHex('#ca6e48')->euclideanDistance(Color::withXYZ(0.0864, 0.0868, 0.1409));
```

Outputs:

```
71.675682240739
```

---

#### dW\Pigmentum\Color::wcag2Contrast ####

Calculate the WCAG2 contrast between `$this` and a provided color.

**NOTE**: While this is currently the standard for the Web it is not terribly accurate nor correct in its assessment. Use only if you're legally bound to do so. Even though the [APCA][g] contrast algorithm is in flux it already is much more accurate than the WCAG2 contrast ratio.

```php
public function wcag2Contrast(
    dW\Pigmentum\Color $color
): dW\Pigmentum\Color;
```

* `color`: Color to calculate contrast against.

##### Example #####

```php
namespace dW\Pigmentum\Color;

echo Color::withRGBHex('#ca6e48')->wcag2Contrast(Color::withXYZ(0.0864, 0.0868, 0.1409));
```

Outputs:

```
2.1117359393426
```

### dW\Pigmentum\ColorSpace\Lab ###

```php
class dW\Pigmentum\ColorSpace\Lab implements \Stringable {
    public readonly float $L;
    public readonly float $a;
    public readonly float $b;


    public function toLCHab(): dW\Pigmentum\ColorSpace\Lab\LCHab;
}
```

#### Properties ####

* *L* (float): The Lightness channel for the color space.
* *a* (float): The a channel for the color space.
* *b* (float): The b channel for the color space.

---

#### dW\Pigmentum\ColorSpace\Lab::toLCHab ####

Returns the LCH (L\*a\*b\*) color space for the color.

```php
public function toLCHab(): dW\Pigmentum\ColorSpace\Lab\LCHab;
```

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48');
echo $color->toLab()->toLCHab() . "\n";
echo $color->Lab->LCHab;
```

Outputs:

```
lchab(56.794104953129, 51.406997165638, 47.294986435325)
lchab(56.794104953129, 51.406997165638, 47.294986435325)
```

### dW\Pigmentum\ColorSpace\RGB ###

```php
class dW\Pigmentum\ColorSpace\RGB implements \Stringable {
    public readonly float $R;
    public readonly float $G;
    public readonly float $B;
    public readonly float $unclampedR;
    public readonly float $unclampedG;
    public readonly float $unclampedB;
    public readonly string $profile;
    public readonly bool $outOfGamut;


    public function changeProfile(?string $profile = null): dW\Pigmentum\ColorSpace\RGB;
    public function convertToWorkingSpace(?string $profile = null): dW\Pigmentum\ColorSpace\RGB;

    public function toHex(): string;
    public function toHSB(): dW\Pigmentum\ColorSpace\RGB\HSB;
}
```

#### Properties ####

* *R* (float): The R channel for the color space
* *G* (float): The G channel for the color space
* *B* (float): The B channel for the color space
* *unclampedR* (float): If the color is out of the profile's gamut this will represent the unclamped R channel for the color space, otherwise same as R
* *unclampedG* (float): If the color is out of the profile's gamut this will represent the unclamped G channel for the color space, otherwise same as G
* *unclampedB* (float): If the color is out of the profile's gamut this will represent the unclamped B channel for the color space, otherwise same as B
* *profile* (string): A string representation of the class name of the color profile the channel values are in
* *outOfGamut* (bool): True if the color is out of the profile's gamut, false if not

---

#### dW\Pigmentum\ColorSpace\RGB::changeProfile ####

Converts the color's profile and returns a RGB color space using the supplied profile.

```php
public function changeProfile(
    ?string $profile = null
): dW\Pigmentum\ColorSpace\RGB;
```

* `profile`: A string representation of the class name of the color profile the channel values are in, defaults to the current working space

##### Example #####

```php
namespace dW\Pigmentum\Color;

// sRGB is the default working space
$color = Color::withRGBHex('#ca6e48');
echo $color->RGB . "\n";
echo $color->RGB->changeProfile(Color::PROFILE_DISPLAYP3);
```

Outputs:

```
rgb(202, 110, 72)
rgb(189.75848271875, 114.65981939776, 80.081758134176)
```

---

#### dW\Pigmentum\ColorSpace\RGB::toHex ####

Returns a RGB hex string for the color.

```php
public function toRGBHex(): string;
```

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48');
echo $color->toRGB()->toHex() . "\n";
echo $color->RGB->Hex;
```

Outputs:

```
lchab(56.794104953129, 51.406997165638, 47.294986435325)
lchab(56.794104953129, 51.406997165638, 47.294986435325)
```

---

#### dW\Pigmentum\ColorSpace\RGB::toHSB ####

Returns the HSB color space for the color.

```php
public function toHSB(): dW\Pigmentum\ColorSpace\RGB\HSB;
```

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48');
echo $color->toRGB()->toHSB() . "\n";
echo $color->RGB->HSB;
```

Outputs:

```
hsb(17.538461538462, 64.356435643564, 79.21568627451)
hsb(17.538461538462, 64.356435643564, 79.21568627451)
```

### dW\Pigmentum\ColorSpace\XYZ ###

```php
class dW\Pigmentum\ColorSpace\XYZ implements \Stringable {
    public function toLMS(): dW\Pigmentum\ColorSpace\XYZ\LMS;
    public function chromaticAdaptation(array $new, array $old): dW\Pigmentum\ColorSpace\XYZ;
}
```

#### Properties ####

* *X* (float): The X channel for the color space.
* *Y* (float): The Y channel for the color space.
* *Z* (float): The Z channel for the color space.

---

#### dW\Pigmentum\ColorSpace\XYZ::toLMS ####

Returns the LMS color space for the color.

```php
public function toLMS(): dW\Pigmentum\ColorSpace\XYZ\LMS;
```

##### Example #####

```php
namespace dW\Pigmentum\Color;

$color = Color::withRGBHex('#ca6e48');
echo $color->toXYZ()->toLMS() . "\n";
echo $color->XYZ->LMS;
```

Outputs:

```
lms(0.34717158449701, 0.18078665440905, 0.067499366253654)
lms(0.34717158449701, 0.18078665440905, 0.067499366253654)
```

---

#### dW\Pigmentum\ColorSpace\XYZ::chromaticAdaptation ####

Converts an XYZ color from one illuminant to another.

```php
public function chromaticAdaptation(array $new, array $old): dW\Pigmentum\ColorSpace\XYZ;
```

* `new`: The new illuminant to convert to
* `old`: The old illuminant

##### Example #####

```php
namespace dW\Pigmentum\Color;

// XYZ D50
$color = Color::withRGBHex('#ca6e48')->XYZ;
echo $color . "\n";
// XYZ D65
echo $color->chromaticAdaptation(Color::ILLUMINANT_D65, Color::ILLUMINANT_D50);
```

Outputs:

```
xyz(0.32686782145478, 0.24712385141221, 0.069650535956488)
xyz(0.31105305562791, 0.24179691491906, 0.091586962737883)
```

### dW\Pigmentum\ColorSpace\Lab\LCHab ###

```php
class dW\Pigmentum\ColorSpace\Lab\LCHab implements \Stringable {
    public readonly float $L;
    public readonly float $C;
    public readonly float $H;
}
```

#### Properties ####

* *L* (float): The Lightness channel for the color space.
* *C* (float): The Chroma channel for the color space.
* *H* (float): The Hue channel for the color space.

### dW\Pigmentum\ColorSpace\RGB\HSB ###

```php
class dW\Pigmentum\ColorSpace\RGB\HSB implements \Stringable {
    public readonly float $H;
    public readonly float $S;
    public readonly float $B;
}
```

#### Properties ####

* *H* (float): The Hue channel for the color space.
* *S* (float): The Saturation channel for the color space.
* *B* (float): The Brightness channel for the color space.

### dW\Pigmentum\ColorSpace\XYZ\LMS ###

```php
class dW\Pigmentum\ColorSpace\XYZ\LMS implements \Stringable {
    public readonly float $rho;
    public readonly float $gamma;
    public readonly float $beta;
}
```

#### Properties ####

* *rho* (float): The Rho (L) channel for the color space.
* *gamma* (float): The Gamma (M) channel for the color space.
* *beta* (float): The Beta (S) channel for the color space.

### dW\Pigmentum\Profile\RGB ###

This is the base abstract color profile class. All RGB color profiles must inherit from this.

```php
abstract class dW\Pigmentum\Profile\RGB {
    const illuminant = dW\Pigmentum\Color::ILLUMINANT_D65;
    const chromaticity = [];
    const gamma = 2.2;
    const name = '';

    protected static array $xyzMatrix;
    protected static array $xyzMatrixInverse;


    public static function companding(float $channel): float;
    public static function inverseCompanding(float $channel): float;
    public static function getXYZMatrix(): MathPHP\LinearAlgebra\Matrix\Matrix;
}
```

### dW\Pigmentum\Profile\RGB\AdobeRGB1998 ###

```php
class dW\Pigmentum\Profile\AdobeRGB1998 extends dW\Pigmentum\Profile\RGB {
    const name = 'Adobe RGB (1998)';

    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.2100, 0.7100 ],
        [ 0.1500, 0.0600 ]
    ];

    protected static array $xyzMatrix = [
        [ 0.5767308871981477, 0.18555395071121408, 0.18818516209063843 ],
        [ 0.29737686371154487, 0.6273490714522, 0.07527406483625537 ],
        [ 0.027034260337413143, 0.0706872193185578, 0.9911085203440292 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 2.04136897926008, -0.5649463871751956, -0.34469438437784833 ],
        [ -0.9692660305051868, 1.8760108454466942, 0.041556017530349834 ],
        [ 0.013447387216170259, -0.11838974235412557, 1.0154095719504166 ]
    ];
}
```

### dW\Pigmentum\Profile\RGB\DisplayP3 ###

```php
class dW\Pigmentum\Profile\AdobeRGB1998 extends dW\Pigmentum\Profile\RGB {
    const name = 'Display P3';

    const chromaticity = [
        [ 0.680, 0.320 ],
        [ 0.265, 0.690 ],
        [ 0.150, 0.060 ]
    ];

    protected static array $xyzMatrix = [
        [ 0.48663265, 0.2656631625, 0.1981741875 ],
        [ 0.2290036, 0.6917267249999999, 0.079269675 ],
        [ -3.972579210032023E-17, 0.04511261250000004, 1.0437173875 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 2.4931807553289667, -0.9312655254971397, -0.40265972375888165 ],
        [ -0.8295031158210787, 1.7626941211197922, 0.02362508874173958 ],
        [ 0.03585362578007169, -0.07618895478265217, 0.9570926215180212 ]
    ];
}
```

### dW\Pigmentum\Profile\RGB\ProPhoto ###

```php
class dW\Pigmentum\Profile\ProPhoto extends dW\Pigmentum\Profile\RGB {
    const name = 'ProPhoto RGB';

    const illuminant = Color::ILLUMINANT_D50;

    const chromaticity = [
        [ 0.7347, 0.2653 ],
        [ 0.1596, 0.8404 ],
        [ 0.0366, 0.0001 ]
    ];

    const gamma = 1.8;

    protected static array $xyzMatrix = [
        [ 0.7976749444306044, 0.13519170147409815, 0.031353354095297416 ],
        [ 0.2880402378623102, 0.7118740972357901, 8.566490189971971E-5 ],
        [ 0, 0, 0.82521 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 1.3459433009386654, -0.25560750931676696, -0.05111176587088495 ],
        [ -0.544598869458717, 1.508167317720767, 0.020535141586646915 ],
        [ 0, 0, 1.2118127506937628 ]
    ];
}
```

### dW\Pigmentum\Profile\RGB\Simple_sRGB ###

```php
class dW\Pigmentum\Profile\Simple_sRGB extends dW\Pigmentum\Profile\RGB {
    const name = 'Simple sRGB';

    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.3000, 0.6000 ],
        [ 0.1500, 0.0600 ]
    ];

    protected static array $xyzMatrix = [
        [ 0.4124564390896922, 0.357576077643909, 0.18043748326639894 ],
        [ 0.21267285140562253, 0.715152155287818, 0.07217499330655958 ],
        [ 0.0193338955823293, 0.11919202588130297, 0.9503040785363679 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 3.2404541621141045, -1.5371385127977166, -0.498531409556016 ],
        [ -0.9692660305051868, 1.8760108454466942, 0.041556017530349834 ],
        [ 0.055643430959114726, -0.2040259135167538, 1.0572251882231791 ]
    ];
}
```

### dW\Pigmentum\Profile\RGB\sRGB ###

```php
class dW\Pigmentum\Profile\Simple_sRGB extends dW\Pigmentum\Profile\RGB {
    const name = 'sRGB IEC61966-2.1';

    const chromaticity = [
        [ 0.6400, 0.3300 ],
        [ 0.3000, 0.6000 ],
        [ 0.1500, 0.0600 ]
    ];

    protected static array $xyzMatrix = [
        [ 0.4124564390896922, 0.357576077643909, 0.18043748326639894 ],
        [ 0.21267285140562253, 0.715152155287818, 0.07217499330655958 ],
        [ 0.0193338955823293, 0.11919202588130297, 0.9503040785363679 ]
    ];

    protected static array $xyzMatrixInverse = [
        [ 3.2404541621141045, -1.5371385127977166, -0.498531409556016 ],
        [ -0.9692660305051868, 1.8760108454466942, 0.041556017530349834 ],
        [ 0.055643430959114726, -0.2040259135167538, 1.0572251882231791 ]
    ];
}
```