<?php

declare(strict_types=1);

namespace SimpleCaptcha;

use SimpleCaptcha\Helpers\A;
use SimpleCaptcha\Helpers\F;
use SimpleCaptcha\Helpers\Dir;
use SimpleCaptcha\Helpers\Str;
use SimpleCaptcha\Helpers\Mime;

use Exception;


/**
 * Class Builder
 *
 * Utilities for generating captcha images
 */
class Builder extends BuilderAbstract
{
    /**
     * Properties
     */

    /**
     * Captcha image
     *
     * As of PHP 8.0, GD functions return an object instead of a resource.
     *
     * @var resource|object
     */
    public $image;
    
    /**
     * @var int $width
     */
    public int $width;

    /**
     * @var int $height
     */
    public int $height;


    /**
     * Path to captcha font(s)
     *
     * @var array
     */
    public ?array $fonts = null;


    /**
     * Whether to distort the image
     *
     * @var bool
     */
    public bool $distort = true;


    /**
     * Whether to interpolate the image
     *
     * @var bool
     */
    public bool $interpolate = true;


    /**
     * Maximum number of lines behind the captcha phrase
     *
     * @var int
     */
    public ?int $maxLinesBehind = null;


    /**
     * Maximum number of lines in front of the captcha phrase
     *
     * @var int
     */
    public ?int $maxLinesFront = null;


    /**
     * Maximum character angle
     *
     * @var int
     */
    public int $maxAngle = 8;


    /**
     * Maximum character offset
     *
     * @var int
     */
    public int $maxOffset = 5;


    /**
     * Background color, either ..
     *
     * (1) .. RGB values (array)
     * (2) .. HEX value (string)
     * (3) .. 'transparent' (string)
     *
     * @var array|string
     */
    public $bgColor = null;


    /**
     * Background color code
     *
     * @var array
     */
    private int $bgCode;


    /**
     * Line color, either ..
     *
     * (1) .. RGB values (array)
     * (2) .. HEX value (string)
     *
     * @var array|string
     */
    public $lineColor = null;


    /**
     * Text color, either ..
     *
     * (1) .. RGB values (array)
     * (2) .. HEX value (string)
     *
     * @var array|string
     */
    public $textColor = null;


    /**
     * Path to background image
     *
     * @var array
     */
    public ?string $bgImage = null;


    /**
     * Whether to apply (any) effects
     *
     * @var bool
     */
    public bool $applyEffects = true;


    /**
     * Whether to apply background noise (using random letters)
     *
     * @var bool
     */
    public bool $applyNoise = true;


    /**
     * Multiples of phrase length to be used for noise generation
     *
     * @var int
     */
    public int $noiseFactor = 2;


    /**
     * Whether to apply post effects
     *
     * @var bool
     */
    public bool $applyPostEffects = true;


    /**
     * Whether to enable scatter effect
     *
     * @var bool
     */
    public bool $applyScatterEffect = true;


    /**
     * Whether to use random font for each symbol
     *
     * @var bool
     */
    public bool $randomizeFonts = true;


    /**
     * Constructor
     *
     * @param string $phrase Captcha phrase
     * @return void
     */
    public function __construct(?string $phrase = null)
    {
        # Build random phrase if missing input or empty string
        $this->phrase = $phrase ?: $this->buildPhrase();

        # Fetch default font files
        $this->fonts = Dir::files(__DIR__ . '/../fonts', null, true);
    }


    /**
     * Methods
     */

    /**
     * Instantiates 'CaptchaBuilder' object
     *
     * @param string $phrase Captcha phrase
     * @return self
     */
    public static function create(?string $phrase = null): self
    {
        return new self($phrase);
    }


    /**
     * Determines (and validates) colors
     *
     * @param string|array $color Color values, either HEX (string) or RGB (array)
     * @return array
     * @throws \Exception
     */
    private function getColor($color): array
    {
        # If value represents RGB values ..
        if (is_array($color)) {
            # .. validate them
            if (count($color) != 3) {
                throw new Exception(sprintf('Invalid RGB colors: "%s"', A::join($color)));
            }

            return $color;
        }

        return Toolkit::hex2rgb($color);
    }


    /**
     * Draws lines over the image
     *
     * @param int $color Line color
     * @return void
     */
    private function drawLine(?int $color = null): void
    {
        # Determine direction at random, being either ..
        # (1) .. horizontal
        if (mt_rand(0, 1)) {
            $Xa = mt_rand(0, $this->width / 2);
            $Ya = mt_rand(0, $this->height);
            $Xb = mt_rand($this->width / 2, $this->width);
            $Yb = mt_rand(0, $this->height);

        # (2) .. vertical
        } else {
            $Xa = mt_rand(0, $this->width);
            $Ya = mt_rand(0, $this->height / 2);
            $Xb = mt_rand(0, $this->width);
            $Yb = mt_rand($this->height / 2, $this->height);
        }

        # Unless line color was provided ..
        if (is_null($color)) {
            # .. assign it
            # (1) Determine colors to be mixed
            $mix = $this->lineColor ?? [
                mt_rand(100, 255),  # red
                mt_rand(100, 255),  # green
                mt_rand(100, 255),  # blue
            ];

            # (2) Normalize RGB values
            $mix = $this->getColor($mix);

            # (3) Mix them up
            $color = imagecolorallocate($this->image, $mix[0], $mix[1], $mix[2]);
        }

        # Randomize thickness & draw line
        imagesetthickness($this->image, mt_rand(1, 3));
        imageline($this->image, $Xa, $Ya, $Xb, $Yb, $color);
    }


    /**
     * Applies background noise (using random letters)
     *
     * @return void
     */
    private function applyNoise(): void
    {
        for ($i = 0; $i < Str::length($this->phrase) * $this->noiseFactor; $i++) {
            # Determine random letter ..
            $character = static::randomCharacter();
            $font = $this->randomFont();

            # .. of random size & color, ..
            $fontSize = mt_rand(5, 10);
            $textColor = imagecolorallocate($this->image, mt_rand(0, 128), mt_rand(0, 128), mt_rand(0, 128));

            # .. random position ..
            $x = mt_rand(0, $this->width);
            $y = mt_rand(0, $this->height);

            # .. random angle ..
            $angle = mt_rand(-45, 45);

            # .. and apply it
            imagettftext($this->image, $fontSize, $angle, $x, $y, $textColor, $font, $character);
        }
    }


    /**
     * Picks random font file
     *
     * @return string
     */
    private function randomFont(): string
    {
        # Pick random font file
        $font = $this->fonts[mt_rand(0, count($this->fonts) - 1)];

        # If it exists ..
        if (F::exists($font)) {
            # .. use it
            return $font;
        }

        # .. fail otherwise
        throw new Exception(sprintf('File does not exist: "%s"', $font));
    }


    /**
     * Writes captcha phrase on captcha image
     *
     * @return void
     */
    private function writePhrase(): void
    {
        # Determine number of characters
        $length = Str::length($this->phrase);

        # Choose random font
        $font = $this->randomFont();

        # Determine text size & start position
        $size = $this->round($this->width / $length) - mt_rand(0, 3) - 1;
        $box = imagettfbbox($size, 0, $font, $this->phrase);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = $this->round(($this->width - $textWidth) / 2);
        $y = $this->round(($this->height - $textHeight) / 2) + $size;

        # Write individual letters ..
        for ($i = 0; $i < $length; $i++) {
            # (1) .. using random font (if enabled)
            if ($this->randomizeFonts) {
                $font = $this->randomFont();
            }

            # (2) .. using random text color (if enabled)
            # (a) Determine colors to be mixed
            $mix = $this->textColor ?? [
                mt_rand(0, 150),  # red
                mt_rand(0, 150),  # green
                mt_rand(0, 150),  # blue
            ];

            # (b) Normalize RGB values
            $mix = $this->getColor($mix);

            # (c) Mix them up
            $textCode = imagecolorallocate($this->image, $mix[0], $mix[1], $mix[2]);

            # Fetch current character & determine its width
            $char = Str::substr($this->phrase, $i, 1);
            $box = imagettfbbox($size, 0, $font, $char);
            $charWidth = $box[2] - $box[0];

            # Randomize angle & offset
            $angle = mt_rand(-$this->maxAngle, $this->maxAngle);
            $offset = mt_rand(-$this->maxOffset, $this->maxOffset);

            # Draw character
            imagettftext($this->image, $size, $angle, $x, $y + $offset, $textCode, $font, $char);

            # Move along
            $x += $charWidth;
        }
    }


    /**
     * Applies post effects
     *
     * @return void
     */
    private function applyPostEffects(): void
    {
        if (!function_exists('imagefilter')) {
            return;
        }

        # Scatter/Noise - Added in PHP 7.4
        $scattered = false;

        if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
            if ($this->applyScatterEffect && mt_rand(0, 3) != 0) {
                $scattered = true;
                imagefilter($this->image, IMG_FILTER_SCATTER, 0, 2, [$this->bgCode]);
            }
        }

        # Negate ?
        if (mt_rand(0, 1) == 0) {
            imagefilter($this->image, IMG_FILTER_NEGATE);
        }

        # Edge ?
        if (!$scattered && mt_rand(0, 10) == 0) {
            imagefilter($this->image, IMG_FILTER_EDGEDETECT);
        }

        # Contrast
        imagefilter($this->image, IMG_FILTER_CONTRAST, mt_rand(-50, 10));

        # Colorize
        if (!$scattered && mt_rand(0, 5) == 0) {
            imagefilter($this->image, IMG_FILTER_COLORIZE, mt_rand(-80, 50), mt_rand(-80, 50), mt_rand(-80, 50));
        }
    }


    /**
     * Interpolates image
     *
     * @param int $x
     * @param int $y
     * @param int $nw
     * @param int $ne
     * @param int $sw
     * @param int $se
     * @return int
     */
    private function interpolate(int $x, int $y, int $nw, int $ne, int $sw, int $se): int
    {
        list($r0, $g0, $b0) = Toolkit::int2rgb($nw);
        list($r1, $g1, $b1) = Toolkit::int2rgb($ne);
        list($r2, $g2, $b2) = Toolkit::int2rgb($sw);
        list($r3, $g3, $b3) = Toolkit::int2rgb($se);

        $cx = 1.0 - $x;
        $cy = 1.0 - $y;

        $m0 = $cx * $r0 + $x * $r1;
        $m1 = $cx * $r2 + $x * $r3;
        $r  = (int) ($cy * $m0 + $y * $m1);

        $m0 = $cx * $g0 + $x * $g1;
        $m1 = $cx * $g2 + $x * $g3;
        $g  = (int) ($cy * $m0 + $y * $m1);

        $m0 = $cx * $b0 + $x * $b1;
        $m1 = $cx * $b2 + $x * $b3;
        $b  = (int) ($cy * $m0 + $y * $m1);

        return ($r << 16) | ($g << 8) | $b;
    }


    /**
     * Makes image background transparent
     *
     * @param resource|object $image
     * @return void
     */
    private function addTransparency($image): void
    {
        imagealphablending($image, false);
        $transparency = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparency);
        imagesavealpha($image, true);
    }


    /**
     * Fetches color of pixel at given coordinates
     *
     * @param float|int $x Horizontal position (or an estimation thereof)
     * @param float|int $y Vertical position (or an estimation thereof)
     * @return int
     */
    private function pixel2int($x, $y): int
    {
        if ($x < 0 || $x >= $this->width || $y < 0 || $y >= $this->height) {
            return $this->bgCode;
        }

        return imagecolorat($this->image, $this->round($x), $this->round($y));
    }


    /**
     * Distorts image
     *
     * @return void
     */
    private function distort(): void
    {
        $image = imagecreatetruecolor($this->width, $this->height);

        # If background transparency is enabled ..
        if ($this->bgColor == 'transparent') {
            # .. apply it
            $this->addTransparency($image);

            # .. initialize background color code
            $this->bgCode = mt_rand(0, 100);
        }

        $X = mt_rand(0, $this->width);
        $Y = mt_rand(0, $this->height);
        $phase = mt_rand(0, 10);
        $scale = 1.1 + mt_rand(0, 10000) / 30000;

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $Vx = $x - $X;
                $Vy = $y - $Y;
                $Vn = sqrt($Vx * $Vx + $Vy * $Vy);

                if ($Vn != 0) {
                    $Vn2 = $Vn + 4 * sin($Vn / 30);
                    $nX  = $X + ($Vx * $Vn2 / $Vn);
                    $nY  = $Y + ($Vy * $Vn2 / $Vn);
                } else {
                    $nX = $X;
                    $nY = $Y;
                }

                $nY = $nY + $scale * sin($phase + $nX * 0.2);

                if ($this->interpolate && $this->bgColor != 'transparent') {
                    $p = $this->interpolate(
                        $this->round($nX - floor($nX)),
                        $this->round($nY - floor($nY)),
                        $this->pixel2int(floor($nX), floor($nY)),
                        $this->pixel2int(ceil($nX), floor($nY)),
                        $this->pixel2int(floor($nX), ceil($nY)),
                        $this->pixel2int(ceil($nX), ceil($nY))
                    );
                } else {
                    $p = $this->pixel2int($this->round($nX), $this->round($nY));
                }

                if ($p == 0) {
                    $p = $this->bgCode;
                }

                imagesetpixel($image, $x, $y, $p);
            }
        }

        $this->image = $image;
    }


    /**
     * Builds captcha image
     *
     * @param int $width Captcha image width
     * @param int $height Captcha image height
     * @return self
     */
    public function build(int $width = 150, int $height = 40): self
    {
        # Apply image dimensions
        $this->width = $width;
        $this->height = $height;

        # If background image available ..
        if (!is_null($this->bgImage)) {
            # (1) .. create image from it
            $this->image = $this->img2gd($this->bgImage);

            # (2) .. extract background color from it
            $this->bgCode = imagecolorat($this->image, 0, 0);

        # .. otherwise ..
        } else {
            # .. start from scratch
            $this->image = imagecreatetruecolor($this->width, $this->height);

            # If background transparency is enabled ..
            if ($this->bgColor == 'transparent') {
                # .. apply it
                $this->addTransparency($this->image);
            }

            # .. otherwise ..
            else {
                # .. assign background color
                # (1) Determine colors to be mixed
                $mix = $this->bgColor ?? [
                    mt_rand(200, 255),  # red
                    mt_rand(200, 255),  # green
                    mt_rand(200, 255),  # blue
                ];

                # (2) Normalize RGB values
                $mix = $this->getColor($mix);

                # (3) Mix them up
                $this->bgCode = imagecolorallocate($this->image, $mix[0], $mix[1], $mix[2]);

                # Fill image
                imagefill($this->image, 0, 0, $this->bgCode);
            }
        }

        # Calculate surface size
        $surface = $this->width * $this->height;

        # Apply effects
        if ($this->applyEffects) {
            $effects = $this->random_float($surface / 3000, $surface / 2000);

            # Set the maximum number of lines to draw in front of the text
            if (is_int($this->maxLinesBehind) && $this->maxLinesBehind > 0) {
                $effects = min($this->maxLinesBehind, $effects);
            }

            if ($this->maxLinesBehind !== 0) {
                for ($e = 0; $e < $effects; $e++) {
                    $this->drawLine();
                }
            }

            # Apply background noise
            if ($this->applyNoise) {
                $this->applyNoise();
            }
        }

        # Write captcha phrase & returns its color code
        $this->writePhrase();

        # Apply effects
        if ($this->applyEffects) {
            $effects = $this->random_float($surface / 3000, $surface / 2000);

            # Set the maximum number of lines to draw in front of the text
            if (is_int($this->maxLinesFront) && $this->maxLinesFront > 0) {
                $effects = min($this->maxLinesFront, $effects);
            }

            if ($this->maxLinesFront !== 0) {
                for ($e = 0; $e < $effects; $e++) {
                    $this->drawLine();
                }
            }

            # Distort the image
            if ($this->distort) {
                $this->distort();
            }
        }

        # Add post effects
        if ($this->applyEffects && $this->applyPostEffects) {
            $this->applyPostEffects();
        }

        return $this;
    }


    /**
     * Creates GD image object from file
     *
     * @param string $image
     * @return resource|object
     * @throws \Exception
     */
    protected function img2gd(string $file)
    {
        # If file does not exist ..
        if (!F::exists($file)) {
            # .. fail early
            throw new Exception(sprintf('File does not exist: "%s"', F::filename($file)));
        }

        $methods = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/gif' => 'imagecreatefromgif',
        ];

        $mime = Mime::type($file);

        if (in_array($mime, array_keys($methods))) {
            return $methods[$mime]($file);
        }

        throw new Exception(sprintf('MIME type "%s" not supported!', $mime));
    }


    /**
     * Creates image content from GD image object
     *
     * @param int $quality Captcha image quality
     * @param string $filename Output filepath
     * @param string $type Captcha image output format
     * @return void
     * @throws \Exception
     */
    protected function gd2img(int $quality = 90, ?string $filename = null, string $type = 'jpg'): void
    {
        # Convert filetype to lowercase
        $type = Str::lower($type);

        # If filename is given ..
        if (!is_null($filename)) {
            # .. determine filetype from it
            $type = F::extension($filename);
        }

        if ($type == 'gif') {
            imagegif($this->image, $filename);
        } elseif ($type == 'jpg') {
            imagejpeg($this->image, $filename, $quality);
        } elseif ($type == 'png') {
            # Normalize quality
            if ($quality > 9) {
                $quality = -1;
            }

            imagepng($this->image, $filename, $quality);
        }

        # .. otherwise ..
        else {
            # .. abort execution
            throw new Exception(sprintf('File type "%s" not supported!', $type));
        }
    }


    /**
     * Saves captcha image to file
     *
     * @param string $filename Output filepath
     * @param int $quality Captcha image quality
     * @return void
     */
    public function save(string $filename, int $quality = 90): void
    {
        $this->gd2img($quality, $filename);
    }


    /**
     * Outputs captcha image directly
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image filetype
     * @return void
     */
    public function output(int $quality = 90, string $type = 'jpg'): void
    {
        $this->gd2img($quality, null, $type);
    }


    /**
     * Fetches captcha image contents
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image filetype
     * @return string
     */
    public function fetch(int $quality = 90, string $type = 'jpg'): string
    {
        # Enable output buffering
        ob_start();
        $this->output($quality, $type);

        return ob_get_clean();
    }


    /**
     * Fetches captcha image as data URI
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image filetype
     * @return string
     */
    public function inline(int $quality = 90, string $type = 'jpg'): string
    {
        return sprintf('data:%s;base64,%s', Mime::fromExtension($type), base64_encode($this->fetch($quality, $type)));
    }


    /**
     * Helpers
     */

    /**
     * Rounds float to integer
     *
     * @param float $number
     * @return int
     */
    private function round(float $number): int
    {
        return (int) round($number);
    }


    /**
     * Creates random float between two digits
     *
     * @param float|int $min
     * @param float|int $max
     * @return float
     */
    private function random_float($min, $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * abs($max - $min);
    }
}
