Pigmentum
=========

Library for manipulating color in PHP.

## Usage ##

### Convert from RGB Hex string to Lab ###

```php
namespace dW\Pigmentum;

$green = Color::withRGBHex('#00af32');
echo $green->Lab; // lab(52.953689965011, -41.955892552796, 35.496587588858)
```

### Convert from RGB Hex string in Adobe RGB (1998) color space to sRGB ###

```php
namespace dW\Pigmentum;

$green = Color::withRGBHex('#33903c', null, Color::PROFILE_ADOBERGB1998);
$green->RGB->convertToProfile(Color::PROFILE_SRGB);
echo $green->RGB; // rgb(0, 145.30458529644, 49.259546335093)
```

**This is a stub. The examples above only show a few things the library can do. **