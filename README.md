Pigmentum
=========

[a]: https://en.wikipedia.org/wiki/CIE_1931_color_space
[b]: https://en.wikipedia.org/wiki/CIELAB_color_space
[c]: https://en.wikipedia.org/wiki/CIE_1931_color_space
[d]: https://en.wikipedia.org/wiki/HSL_and_HSV
[e]: https://en.wikipedia.org/wiki/LMS_color_space
[f]: https://en.wikipedia.org/wiki/RGB_color_space

Library for manipulating color in PHP. This is the result of my own experiments with color math. There are other color classes out there, but they either work not how I'd like or the math is incorrect.

## Warning Before Using ##

This library is experimental. The code does not have any unit tests yet, but that work is planned. Until unit tests exist, treat this software as beta or even alpha software. Also, the public API is in flux, so if you do use this library you're forewarned of possible breaking API changes.

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

Color::withRGB(57, 35, 38);
```

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

Color::withRGB(202, 110, 72);
```

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

Color::withRGBHex('#ca6e48');
```

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

#### dW\Pigmentum\Color::toLab ####

Returns the L\*a\*b\* color space for the color.

```php
public static function toLab(): dW\Pigmentum\ColorSpace\Lab;
```

#### dW\Pigmentum\Color::toRGB ####

Returns the RGB color space for the color.

```php
public static function toRGB(
    ?string $profile = null
): dW\Pigmentum\ColorSpace\RGB;
```

* `profile`: A string representation of the class name of the color profile the channel values are in, defaults to the current working space

#### dW\Pigmentum\Color::toXYZ ####

Returns the XYZ color space for the color.

```php
public static function toXYZ(): dW\Pigmentum\ColorSpace\XYZ;
```