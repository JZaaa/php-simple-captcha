# Captcha

![Captcha examples](captchas.png)

**Note:** This library is being *heavily* refactored right now, as we're heading straight towards v2!


## Installation

With composer :

``` json
{
    ...
    "require": {
        "gregwar/captcha": "1.*"
    }
}
```

## Usage

You can create a captcha with the `CaptchaBuilder` :

```php
<?php

use SimpleCaptcha\CaptchaBuilder;

$builder = new CaptchaBuilder;
$builder->build();
```

You can then save it to a file :

```php
<?php

$builder->save('out.jpg');
```

Or output it directly :

```php
<?php

header('Content-type: image/jpeg');
$builder->output();
```

Or inline it directly in the HTML page:

```php
<img src="<?php echo $builder->inline(); ?>" />
```

You'll be able to get the code and compare it with a user input:

```php
<?php

// Example: storing the phrase in the session to test for the user input later
$_SESSION['phrase'] = $builder->getPhrase();
```

**Note:** Since one of the randomly selected fonts may contain only uppercase letters, you should compare the phrase with user input using `compare()`, otherwise you might end up with unsolvable captchas:

```php
if ($builder->compare($userInput)) {
    // instructions if user phrase is good
}

else {
    // user phrase is wrong
}
```

## API

You can use theses functions :

* **__construct($phrase = null)**, constructs the builder with the given phrase, if the phrase is null, a random one will be generated
* **getPhrase()**, allow you to get the phrase contents
* **compare($phrase)**, returns true if the given phrase is good
* **setDistortion($distortion)**, enable or disable the distortion, call it before `build()`
* **isOCRReadable()**, returns `true` if the OCR can be read using the `ocrad` software, you'll need to have shell_exec enabled, imagemagick and ocrad installed
* **buildAgainstOCR($width = 150, $height = 40, $font = null)**, builds a code until it is not readable by `ocrad`
* **build($width = 150, $height = 40, $font = null)**, builds a code with the given $width, $height and $font. By default, a random font will be used from the library
* **save($filename, $quality = 90)**, saves the captcha into a jpeg in the $filename, with the given quality
* **inline($quality = 90)**, returns jpeg data as data URI (including MIME type, eg `data:image/jpeg;base64,<base64-encoded string>`)
* **fetch($quality = 90)**, returns the jpeg data
* **output($quality = 90)**, directly outputs the jpeg code to a browser
* **setBackgroundColor([$r, $g, $b])**, sets the background color to force it (this will disable many effects and is not recommended)
* **setBackgroundImages([$imagepath1, $imagePath2])**, Sets custom background images to be used as captcha background. It is recommended to disable image effects when passing custom images for background (ignore_all_effects). A random image is selected from the list passed, the full paths to the image files must be passed.
* **setInterpolation($interpolate)**, enable or disable the interpolation (enabled by default), disabling it will be quicker but the images will look uglier
* **setApplyAllEffects($applyAllEffects)**, enables all effects on the captcha image. Setting `false` may prove useful when passing custom background images for the captcha.
* **setApplyPostEffects($applyPostEffects)**, enables post effects on the captcha image. Setting `false` may prove useful when passing custom background images for the captcha.
* **setApplyScatterEffect($applyScatterEffect)**, enables scatter effect (PHP version >= 7.4).
* **setMaxBehindLines($lines)**, sets the maximum number of lines behind the code
* **setMaxFrontLines($lines)**, sets the maximum number of lines on the front of the code

If you want to change the number of character, you can call the phrase builder directly using
extra parameters:

```php
use SimpleCaptcha\CaptchaBuilder;
use SimpleCaptcha\PhraseBuilder;

// Will build phrases of 3 characters
$phraseBuilder = new PhraseBuilder(4);

// Will build phrases of 5 characters, only digits
$phraseBuilder = new PhraseBuilder(5, '0123456789');

// Pass it as first argument of CaptchaBuilder, passing it the phrase
// builder
$captcha = new CaptchaBuilder(null, $phraseBuilder);
```

You can also pass directly the wanted phrase to the builder:

```php
// Building a Captcha with the "hello" phrase
$captcha = new CaptchaBuilder('hello');
```

# Complete example

If you want to see an example you can have a look at the ``demo/form.php``, which uses ``demo/session.php`` to
render a captcha and check it after the submission

## Symfony Bundle

You can have a look at the following repository to enjoy the Symfony 2 bundle packaging this captcha generator :
https://github.com/Gregwar/CaptchaBundle

## Yii2 Extension

You can use the following extension for integrating with Yii2 Framework :
https://github.com/juliardi/yii2-captcha

## License

This library is under MIT license, have a look to the `LICENSE` file
