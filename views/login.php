<?php
declare(strict_types=1);

ob_start();
$flash = Session::flash('success');
?>
<header class="topbar">
  <div class="brand">
    <div class="brand-main">
      <img class="brand-logo" src="/assets/img/logo.svg" alt="Plughub POS logo" />
      <div class="brand-text">
        <h1><?= e($title ?? 'Plughub POS Mobile') ?></h1>
        <div class="brand-sub">POS on the go</div>
      </div>
    </div>
    <div class="pill">Sign in</div>
  </div>
</header>

<main class="content content-center">
  <div class="hero-tagline">
    <span class="hero-icon">🛒</span>
    <span>Sell smarter. Track everything.</span>
  </div>

  <section class="panel">
    <h2>Login</h2>

    <?php if (is_string($flash) && $flash !== ''): ?>
      <div class="notice notice-ok"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if (is_string($error ?? null) && $error !== ''): ?>
      <div class="notice notice-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>" />

      <div class="microcopy">Welcome back 👋</div>

      <label class="label">
        <span>Username</span>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input class="input icon" name="username" autocomplete="username" inputmode="text" value="<?= e((string) ($username ?? '')) ?>" />
        </div>
      </label>

      <label class="label">
        <span>Password</span>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input class="input icon" type="password" name="password" autocomplete="current-password" />
        </div>
      </label>

      <button class="btn btn-primary" type="submit">Login</button>
      <div class="meta-line">Fast • Simple • Reliable</div>
    </form>
  </section>

  <div class="trustline">
    <span>🔒</span>
    <span>Secure login powered by PlugHub</span>
  </div>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
