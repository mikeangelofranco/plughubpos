<?php
declare(strict_types=1);

ob_start();
?>
<header class="topbar">
  <div class="brand">
    <div class="brand-main">
      <img class="brand-logo" src="/assets/img/plughubpos-logo.jpg" alt="Plughub POS logo" />
      <div class="brand-text">
        <h1><?= e($title ?? 'Mobile POS') ?></h1>
        <div class="brand-sub">Page not found</div>
      </div>
    </div>
    <div class="pill">404</div>
  </div>
</header>

<main class="content content-center">
  <section class="panel">
    <h2>Page not found</h2>
    <div class="notice notice-error">No route for: <?= e((string) ($path ?? '')) ?></div>
    <a class="btn btn-primary" href="/">Go to POS</a>
    <a class="btn btn-ghost" href="/login">Login</a>
  </section>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
