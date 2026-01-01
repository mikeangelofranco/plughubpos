<?php
declare(strict_types=1);

$hide_demo_watermark = true;
ob_start();
$flash = Session::flash('success');
?>
<header class="topbar">
  <div class="brand">
    <div class="brand-main">
      <img class="brand-logo" src="/assets/img/plughubpos-logo.jpg" alt="Plughub POS logo" />
      <div class="brand-text">
        <h1><?= e($title ?? 'Mobile POS') ?></h1>
        <div class="brand-sub">Mobile POS for small to medium stores</div>
      </div>
    </div>
    <div class="pill">Demo Mode</div>
  </div>
</header>

<main class="content login-page" data-login-screen>
  <section class="panel hero-card login-block" style="--delay:0ms;">
    <h2 class="hero-title">Sell faster. Track smarter.</h2>
    <p class="hero-subtitle">From Seller Screen to Receipt in seconds. Simple, reliable, and made for daily sales.</p>
    <div class="hero-highlights">
      <div class="hero-highlight">
        <span class="highlight-icon highlight-fast" aria-hidden="true"></span>
        <span>Fast Checkout</span>
      </div>
      <div class="hero-highlight">
        <span class="highlight-icon highlight-history" aria-hidden="true"></span>
        <span>Sales History + Reprint</span>
      </div>
      <div class="hero-highlight">
        <span class="highlight-icon highlight-inventory" aria-hidden="true"></span>
        <span>Inventory Adjust + Price Change</span>
      </div>
    </div>
  </section>

  <section class="panel demo-card login-block" style="--delay:60ms;">
    <div class="demo-head">
      <h2 class="panel-title">Try the Demo Login</h2>
      <div class="microcopy">Use the credentials below to explore the demo version.</div>
    </div>

    <div class="demo-credentials">
      <div class="credential-row">
        <span class="credential-label">Username</span>
        <div class="credential-chip">
          <input class="credential-input" type="text" value="admin" readonly data-demo-username />
        </div>
      </div>
      <div class="credential-row">
        <span class="credential-label">Password</span>
        <div class="credential-chip">
          <input class="credential-input" type="password" value="Admin123!" readonly data-demo-password />
          <button class="chip-toggle" type="button" data-toggle-demo-password aria-pressed="false">Show</button>
        </div>
      </div>
    </div>

    <div class="demo-actions">
      <button class="btn btn-primary login-btn" type="button" data-demo-fill>Use Demo Credentials</button>
      <button class="btn btn-ghost login-btn" type="button" data-demo-copy>Copy Credentials</button>
      <button class="inline-link" type="button" data-demo-reset>Reset Fields</button>
    </div>
  </section>

  <section class="panel login-panel login-block" style="--delay:120ms;">
    <div class="login-head">
      <h2 class="panel-title">Sign in to continue</h2>
      <div class="microcopy">For demo access, tap "Use Demo Credentials" above.</div>
    </div>

    <?php if (is_string($flash) && $flash !== ''): ?>
      <div class="notice notice-ok"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if (is_string($error ?? null) && $error !== ''): ?>
      <div class="notice notice-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>" />

      <label class="label">
        <span>Username</span>
        <div class="input-wrap">
          <input
            class="input"
            id="login-username"
            name="username"
            autocomplete="username"
            inputmode="text"
            placeholder="Enter username"
            value="<?= e((string) ($username ?? '')) ?>"
            data-login-username
          />
        </div>
      </label>

      <label class="label">
        <span>Password</span>
        <div class="input-wrap has-action">
          <input
            class="input"
            id="login-password"
            type="password"
            name="password"
            autocomplete="current-password"
            placeholder="Enter password"
            data-login-password
          />
          <button class="input-toggle" type="button" data-toggle-login-password aria-pressed="false">Show</button>
        </div>
      </label>

      <button class="btn btn-primary login-btn" type="submit">Sign In</button>
      <div class="meta-line">Fast checkout flow, built for real daily selling.</div>
    </form>
  </section>

  <section class="panel feature-proof login-block" style="--delay:180ms;">
    <h3>What you can try in the demo</h3>
    <ul class="feature-list">
      <li><span class="feature-icon" aria-hidden="true"></span>Seller Screen for quick item selection</li>
      <li><span class="feature-icon" aria-hidden="true"></span>Smooth checkout from cart to sale complete</li>
      <li><span class="feature-icon" aria-hidden="true"></span>Sales History with receipt reprints</li>
      <li><span class="feature-icon" aria-hidden="true"></span>Inventory adjustment for stock updates</li>
      <li><span class="feature-icon" aria-hidden="true"></span>Price change for quick product updates</li>
    </ul>
  </section>

  <div class="trustline login-block" style="--delay:240ms;">
    <div class="trustline-title">Secure demo access powered by PlugHub</div>
    <div class="trustline-sub">Demo data only. For preview and testing.</div>
  </div>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
