<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleCaptcha\Builder;

function clearCaptcha(bool $lowInterference = false): Builder
{
    $captcha = Builder::create();

    $captcha->bgColor = '#f5f7fb';
    $captcha->textColor = '#1f2937';
    $captcha->lineColor = '#d6dde8';
    $captcha->applyNoise = false;
    $captcha->distort = false;
    $captcha->applyPostEffects = false;
    $captcha->randomizeFonts = false;

    if ($lowInterference) {
        $captcha->applyEffects = true;
        $captcha->maxLinesBehind = 1;
        $captcha->maxLinesFront = 0;
    } else {
        $captcha->applyEffects = false;
    }

    return $captcha->build(180, 52);
}

$plain = clearCaptcha();
$soft = clearCaptcha(true);

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Clear Captcha Demo</title>
        <style>
            body {
                margin: 32px;
                color: #1f2937;
                font-family: Arial, sans-serif;
            }

            img {
                display: block;
                margin: 8px 0 12px;
                border: 1px solid #d6dde8;
            }

            code {
                background: #f5f7fb;
                padding: 2px 5px;
            }
        </style>
    </head>
    <body>
        <h1>Clear Captcha Demo</h1>

        <h2>No interference</h2>
        <img src="<?= $plain->inline() ?>" alt="Clear captcha without interference">
        <p>Phrase: <code><?= htmlspecialchars($plain->phrase, ENT_QUOTES, 'UTF-8') ?></code></p>

        <h2>Low interference</h2>
        <img src="<?= $soft->inline() ?>" alt="Clear captcha with low interference">
        <p>Phrase: <code><?= htmlspecialchars($soft->phrase, ENT_QUOTES, 'UTF-8') ?></code></p>
    </body>
</html>
