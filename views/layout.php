<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="theme-color" content="#ffffff" />
    <title><?= e($title ?? 'POS') ?></title>
    <link rel="icon" href="data:;base64,=" />
    <link rel="stylesheet" href="/assets/css/app.css" />
  </head>
  <body>
    <div class="app">
      <div class="shell">
        <?= $content ?? '' ?>
      </div>
    </div>
    <script src="/assets/js/app.js" defer></script>
  </body>
</html>
