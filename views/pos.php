<?php
declare(strict_types=1);

ob_start();
$user = is_array($user ?? null) ? $user : null;
$flash = is_string($flash ?? null) ? $flash : null;
$flashError = is_string($flash_error ?? null) ? $flash_error : null;
$role = is_string($role ?? null) ? $role : (string) ($user['role'] ?? 'Cashier');
$roleLower = strtolower($role);
$isAdmin = $roleLower === 'admin';
$isManager = $roleLower === 'manager';
$isReadonly = $roleLower === 'readonly';
$tenantId = isset($tenant_id) && is_numeric($tenant_id) ? (int) $tenant_id : null;
$tenantName = is_string($tenant_name ?? null) ? $tenant_name : 'Tenant';
$tenantOptions = is_array($tenants ?? null) ? $tenants : [];
$csrf = Csrf::token();
?>
<div class="screen" data-pos-screen data-role="<?= e($roleLower) ?>" data-tenant="<?= e((string) $tenantId) ?>">
  <header class="topbar">
    <div class="topbar-row topbar-head">
      <div class="menu-wrap">
        <button class="icon-btn" type="button" aria-label="Open menu" data-menu-open>&#9776;</button>
        <div class="menu-dropdown" data-menu-dropdown>
          <?php if ($isAdmin): ?>
            <a href="/tenant-config">Tenant Configuration</a>
          <?php elseif ($isManager): ?>
            <a href="/manage-users">Manage Users</a>
          <?php endif; ?>
          <a href="/login">Switch user</a>
          <a href="/logout">Logout</a>
        </div>
      </div>
      <div class="brand-text">
        <div class="brand-name"><?= e($title ?? 'Plughub POS Mobile') ?></div>
        <div class="brand-sub">Multi-tenant mode</div>
      </div>
      <div class="user-chip">
        <span class="user-icon">&#128100;</span>
        <span><?= $user ? e((string) ($user['username'] ?? '')) : 'Cashier' ?></span>
      </div>
    </div>
    <div class="topbar-row meta-row">
      <div class="role-chip role-<?= e($roleLower) ?>"><?= e(ucfirst($roleLower ?: 'role')) ?></div>
      <?php if ($isAdmin): ?>
        <form class="tenant-form" method="post" action="/switch-tenant">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
          <label class="tenant-label">
            <span>Tenant</span>
            <select name="tenant_id" class="tenant-select" data-tenant-select>
              <option value="">All tenants</option>
              <?php foreach ($tenantOptions as $t):
                $id = (int) ($t['id'] ?? 0);
                $label = (string) ($t['name'] ?? ('Tenant ' . $id));
                $active = (bool) ($t['active'] ?? true);
                ?>
                <option value="<?= $id ?>" <?= $tenantId === $id ? 'selected' : '' ?>>
                  <?= e($label) ?><?= $active ? '' : ' (inactive)' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        </form>
      <?php else: ?>
        <div class="tenant-chip">
          <span class="tenant-label">Tenant</span>
          <span class="tenant-name"><?= e($tenantName) ?></span>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <main class="content">
    <?php if ($flash): ?>
      <section class="panel">
        <div class="notice notice-ok"><?= e($flash) ?></div>
      </section>
    <?php endif; ?>
    <?php if ($flashError): ?>
      <section class="panel">
        <div class="notice notice-error"><?= e($flashError) ?></div>
      </section>
    <?php endif; ?>
    <?php if ($isReadonly): ?>
      <section class="panel">
        <div class="notice notice-warn">Read-only: you can browse items and reports, but selling is disabled.</div>
      </section>
    <?php endif; ?>

    <section class="panel">
      <div class="search">
        <div class="search-wrap">
          <span class="search-icon">&#128269;</span>
          <span class="camera-icon">&#128247;</span>
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
          <span class="icon">&#128722;</span>
          <span class="count" data-cart-count>0 items</span>
        </div>
          <span class="total" data-cart-total>₱0.00</span>
      </div>
      <button class="btn btn-primary" type="button" data-view-cart>View Cart</button>
    </div>
  </div>
</div>

<div class="cart-overlay" data-cart-overlay>
  <div class="cart-drawer">
    <div class="drawer-header">
      <button class="drawer-back" type="button" data-cart-close aria-label="Close cart">←</button>
      <div class="drawer-title">Cart</div>
      <div class="drawer-icon">&#128722;</div>
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
          <button class="btn btn-ghost" type="button" data-discount-dec>-</button>
          <input class="discount-input" type="number" min="0" step="0.01" inputmode="decimal" data-discount-input aria-label="Discount amount" />
          <button class="btn btn-ghost" type="button" data-discount-inc>+</button>
        </div>
        <div class="value" data-cart-discount>₱0.00</div>
      </div>
    </div>
    <div class="cart-footer">
      <div>
        <div class="label">Total</div>
        <div class="total" data-cart-total-drawer>₱0.00</div>
      </div>
      <button class="btn btn-primary btn-elevated" type="button" data-start-checkout>Checkout</button>
    </div>
  </div>
</div>

<div class="screen checkout-screen is-hidden" data-checkout-screen>
  <header class="checkout-bar">
    <button class="drawer-back" type="button" data-checkout-back aria-label="Back to cart">←</button>
    <div class="checkout-title">Checkout</div>
    <div class="checkout-icon" aria-hidden="true">💳</div>
  </header>

  <main class="checkout-body">
    <section class="panel checkout-total">
      <div class="label">Total To Pay</div>
      <div class="amount" data-checkout-total>₱0.00</div>
      <div class="note">(Discount already applied)</div>
    </section>

    <section class="panel">
      <div class="section-title">Select Payment Method</div>
      <div class="pay-options">
        <button class="pay-btn active" type="button" data-pay-method="cash">
          <span class="pay-icon">💵</span>
          <span>Cash</span>
          <span class="pay-check" aria-hidden="true">✓</span>
        </button>
        <button class="pay-btn" type="button" data-pay-method="qr">
          <span class="pay-icon">🔲</span>
          <span>QR Code</span>
          <span class="pay-check" aria-hidden="true">✓</span>
        </button>
      </div>
    </section>

    <section class="panel checkout-summary">
      <div class="section-title">Payment Summary</div>
      <div class="summary-row">
        <span>Subtotal</span>
        <span data-checkout-subtotal>₱0.00</span>
      </div>
      <div class="summary-row">
        <span>Discount</span>
        <span data-checkout-discount>₱0.00</span>
      </div>
      <div class="divider"></div>
      <div class="summary-row due">
        <div class="due-text">
          <div class="micro-label">Amount to collect</div>
          <div class="label-strong">Total Due</div>
        </div>
        <div class="due-amount" data-checkout-due>₱0.00</div>
      </div>
    </section>
    <section class="panel">
      <button class="btn btn-primary btn-elevated btn-block btn-confirm" type="button" data-confirm-payment>
        <span class="btn-icon">🔒</span>
        Confirm Payment
        <span class="btn-check">✓</span>
      </button>
      <div class="btn-note">Payment will be recorded</div>
    </section>
  </main>
</div>

<div class="cash-overlay is-hidden" data-cash-overlay>
  <div class="cash-sheet">
    <header class="cash-header">
      <button class="drawer-back" type="button" data-cash-close aria-label="Back to checkout">←</button>
      <div>
        <div class="checkout-title">Enter Amount Received</div>
        <div class="cash-subtitle">Tap a quick amount or choose exact</div>
      </div>
    </header>
    <div class="cash-body">
      <div class="cash-line cash-due">
        <div>
          <div class="micro-label">Amount to collect</div>
          <div class="label-strong">Total Due</div>
        </div>
        <div class="cash-amount cash-due-value" data-cash-due>₱0.00</div>
      </div>
      <div class="cash-grid" data-cash-buttons>
        <button class="pill cash-btn" type="button" data-cash-amount="100000">₱1,000</button>
        <button class="pill cash-btn" type="button" data-cash-amount="50000">₱500</button>
        <button class="pill cash-btn" type="button" data-cash-amount="20000">₱200</button>
        <button class="pill cash-btn" type="button" data-cash-amount="10000">₱100</button>
        <button class="pill cash-btn" type="button" data-cash-amount="5000">₱50</button>
        <button class="pill cash-btn accent" type="button" data-cash-amount="enter">Enter Amount</button>
      </div>
      <div class="divider-line"></div>
      <div class="cash-input-row is-hidden" data-cash-input-row>
        <label class="cash-input-label" for="cash-input">Type amount</label>
        <div class="cash-input-wrap">
          <span class="prefix">₱</span>
          <input id="cash-input" class="cash-input" type="number" min="0" step="0.01" inputmode="decimal" data-cash-input placeholder="0.00" />
        </div>
      </div>
      <div class="divider-line"></div>
      <div class="cash-line">
        <span>Cash Amount:</span>
        <span class="cash-amount" data-cash-amount-display>₱0.00</span>
      </div>
      <div class="cash-line" data-cash-line>
        <span class="cash-change-label" data-cash-change-label>Change:</span>
        <span class="cash-change" data-cash-change>₱0.00</span>
      </div>
      <button class="btn btn-primary btn-elevated btn-block" type="button" data-complete-sale>Complete Sale</button>
    </div>
  </div>
</div>

<div class="screen complete-screen is-hidden" data-sale-complete-screen>
  <header class="complete-bar">
    <div class="complete-left">
      <span class="complete-icon">✅</span>
      <div>
        <div class="complete-title">Sale Complete</div>
        <div class="complete-sub">Payment received successfully.</div>
        <div class="complete-meta" data-complete-ts>—</div>
      </div>
    </div>
  </header>
  <main class="complete-body">
    <section class="panel">
      <div class="section-title">Sale Summary</div>
      <div class="complete-hero">
        <div class="hero-label">Amount Received</div>
        <div class="hero-amount" data-complete-paid>₱0.00</div>
      </div>
      <div class="hero-sub hero-sub-outer">
        <span class="badge badge-success" data-complete-change>Change Due ₱0.00</span>
      </div>
      <div class="breakdown">
        <div class="break-row">
          <span>Sale Total</span>
          <span data-complete-sale-total>₱0.00</span>
        </div>
        <div class="break-row">
          <span>Amount Received</span>
          <span data-complete-amount-received>₱0.00</span>
        </div>
        <div class="break-row">
          <span>Change Due</span>
          <span data-complete-change-due>₱0.00</span>
        </div>
      </div>
      <div class="summary-stack compact listy">
        <div class="stack-row">
          <span class="stack-label">Subtotal</span>
          <span class="stack-value" data-complete-subtotal>₱0.00</span>
        </div>
        <div class="stack-row">
          <span class="stack-label">Discount Applied</span>
          <span class="stack-value" data-complete-discount>₱0.00</span>
        </div>
        <div class="stack-row">
          <span class="stack-label">Change Given</span>
          <span class="stack-value" data-complete-change-detail>₱0.00</span>
        </div>
        <div class="stack-row">
          <span class="stack-label">Items</span>
          <span class="stack-value items-action">
            <span data-complete-items>0</span>
            <button class="link inline-link" type="button" data-view-details>View Cart ›</button>
          </span>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="section-title">Payment Details</div>
      <div class="stack-row">
        <span class="stack-label">Payment Method</span>
        <span class="stack-value method">
          <span class="method-icon" data-complete-method-icon>💵</span>
          <span data-complete-method>—</span>
        </span>
      </div>
      <div class="stack-row txn-row">
        <span class="stack-label">Transaction ID</span>
        <span class="stack-value txn">
          <span data-complete-txn>—</span>
          <button class="btn btn-ghost btn-copy" type="button" data-copy-txn>Copy</button>
        </span>
      </div>
    </section>

    <section class="panel complete-actions">
      <button class="btn btn-primary btn-elevated btn-block" type="button" data-new-sale>New Sale</button>
      <div class="secondary-actions">
        <button class="btn btn-ghost" type="button" data-print-receipt>Print</button>
        <button class="btn btn-ghost" type="button" data-send-receipt>Send</button>
      </div>
    </section>
  </main>
</div>

<div class="overlay complete-cart-overlay is-hidden" data-complete-cart-overlay>
  <div class="complete-cart-sheet">
    <header class="complete-cart-header">
      <div>
        <div class="complete-title">Sale Details</div>
        <div class="complete-sub">Completed Transaction</div>
      </div>
      <div class="complete-meta" data-complete-cart-count>0 items</div>
      <button class="drawer-back" type="button" data-complete-cart-close aria-label="Close cart view">←</button>
    </header>
    <div class="complete-cart-body" data-complete-cart-list></div>
    <div class="complete-cart-summary">
      <div class="summary-divider"></div>
      <div class="summary-rows">
        <div class="summary-row-line">
          <span>Subtotal</span>
          <span data-complete-cart-subtotal>₱0.00</span>
        </div>
        <div class="summary-row-line discount">
          <span>Discount</span>
          <span data-complete-cart-discount>₱0.00</span>
        </div>
        <div class="summary-row-line amount-due">
          <span>Amount Due</span>
          <span data-complete-cart-total>₱0.00</span>
        </div>
      </div>
      <div class="summary-rows snapshot">
        <div class="summary-row-line">
          <span>Amount Received</span>
          <span data-complete-cart-paid>₱0.00</span>
        </div>
        <div class="summary-row-line">
          <span>Change Given</span>
          <span data-complete-cart-change>₱0.00</span>
        </div>
        <div class="summary-row-line">
          <span>Payment Method</span>
          <span data-complete-cart-method>—</span>
        </div>
      </div>
    </div>
    <div class="complete-cart-note">This sale is locked and cannot be modified.</div>
  </div>
</div>
<?php
$ctx = [
    'role' => $role,
    'is_admin' => $isAdmin,
    'is_readonly' => $isReadonly,
    'tenant_id' => $tenantId,
    'tenant_name' => $tenantName,
];
$contextJson = json_encode($ctx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$content = ob_get_clean() . PHP_EOL . '<script>window.__APP_CTX__ = ' . $contextJson . ';</script>';
require __DIR__ . '/layout.php';
