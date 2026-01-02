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
    <?php if (!($hide_demo_watermark ?? false)): ?>
      <div class="demo-watermark">DEMO VERSION</div>
    <?php endif; ?>
    <details class="support-fab" data-support-fab>
      <summary class="support-fab__toggle" aria-label="Contact support">
        <span class="support-fab__label">Questions?</span>
        <span class="support-fab__icon" aria-hidden="true">?</span>
      </summary>
      <div class="support-fab__card">
        <div class="support-fab__title">Contact Us</div>
        <a class="support-fab__link" href="mailto:support@plughub-ims.com">support@plughub-ims.com</a>
        <a class="support-fab__link" href="tel:+639927870036">+63 992 787 0036</a>
      </div>
    </details>
    <div class="app">
      <div class="shell">
        <?= $content ?? '' ?>
      </div>
    </div>
    <script src="/assets/js/app.js" defer></script>
  </body>
</html>
