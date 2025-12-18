<?php
declare(strict_types=1);

ob_start();
$user = is_array($user ?? null) ? $user : null;
$flash = is_string($flash ?? null) ? $flash : null;
?>
<header class="topbar">
  <div class="topbar-row">
    <div class="menu-wrap">
      <button class="icon-btn" type="button" aria-label="Open menu" data-menu-open>☰</button>
      <div class="menu-dropdown" data-menu-dropdown>
        <a href="/login">Switch user</a>
        <a href="/logout">🚪 Logout</a>
      </div>
    </div>
    <div class="brand-text">
      <div class="brand-name"><?= e($title ?? 'Plughub POS Mobile') ?></div>
    </div>
    <div class="user-chip">
      <span class="user-icon">👤</span>
      <span><?= $user ? e((string) ($user['username'] ?? '')) : 'Cashier' ?></span>
    </div>
  </div>
</header>

<main class="content">
  <?php if ($flash): ?>
    <section class="panel">
      <div class="notice notice-ok"><?= e($flash) ?></div>
    </section>
  <?php endif; ?>

  <section class="panel">
    <div class="search">
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <span class="camera-icon">📷</span>
        <input class="input search-input" data-search placeholder="Search products, SKU, or barcode" inputmode="search" />
      </div>
    </div>
    <div class="categories" data-categories></div>
  </section>

  <section class="panel">
    <h2>Products</h2>
    <div class="grid" data-products></div>
  </section>
</main>

<div class="cartbar">
  <div class="cartbar-inner">
    <div class="cart-summary">
      <div class="left">
        <span class="icon">🛒</span>
        <span class="count" data-cart-count>0 items</span>
      </div>
        <span class="total" data-cart-total>₱0.00</span>
    </div>
    <button class="btn btn-primary" type="button" data-view-cart>View Cart</button>
  </div>
</div>

<div class="cart-overlay" data-cart-overlay>
  <div class="cart-drawer">
    <div class="drawer-header">
      <button class="drawer-back" type="button" data-cart-close aria-label="Close cart">←</button>
      <div class="drawer-title">Cart</div>
      <div class="drawer-icon">🛒</div>
    </div>
    <div class="cart-items" data-cart-list></div>
    <div class="cart-totals">
      <div class="cart-line">
        <div class="label">Subtotal</div>
        <div class="value" data-cart-subtotal>₱0.00</div>
      </div>
      <div class="cart-line">
        <div class="label">Discount (₱)</div>
        <div class="discount-ctrl">
          <button class="btn btn-ghost" type="button" data-discount-dec>−</button>
          <input class="discount-input" type="number" min="0" step="0.01" inputmode="decimal" data-discount-input aria-label="Discount amount" />
          <button class="btn btn-ghost" type="button" data-discount-inc>+</button>
        </div>
      </div>
    </div>
    <div class="cart-footer">
      <div>
        <div class="label">Total</div>
        <div class="total" data-cart-total-drawer>₱0.00</div>
      </div>
    <button class="btn btn-primary btn-elevated" type="button">🔒 Checkout</button>
  </div>
</div>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
