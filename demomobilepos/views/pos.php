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
$tenantAddress = is_string($tenant_address ?? null) ? $tenant_address : '';
$tenantContact = is_string($tenant_contact ?? null) ? $tenant_contact : '';
$tenantOptions = is_array($tenants ?? null) ? $tenants : [];
$csrf = Csrf::token();
$cashierFull = is_string($user['full_name'] ?? null) ? $user['full_name'] : '';
$usernameDisplay = is_string($user['username'] ?? null) ? $user['username'] : 'phga_manager';
$avatarInitials = strtoupper(substr($usernameDisplay, 0, 2));
$roleLabel = ucfirst($roleLower ?: 'Role');
?>
<div class="screen" data-pos-screen data-role="<?= e($roleLower) ?>" data-tenant="<?= e((string) $tenantId) ?>">
  <header class="topbar">
    <div class="topbar-row topbar-head">
      <div class="menu-wrap">
        <button class="icon-btn burger-btn" type="button" aria-label="Open menu" data-menu-open>
          <span class="burger-lines" aria-hidden="true">
            <span></span><span></span><span></span>
          </span>
        </button>
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

  <div class="nav-drawer-shell" data-menu-shell>
    <div class="nav-drawer-overlay" data-menu-overlay></div>
    <aside class="nav-drawer" data-menu-drawer role="dialog" aria-modal="true" aria-label="Plughub POS menu" aria-hidden="true">
      <div class="nav-drawer-header">
        <div class="nav-app">
          <div class="nav-app-name">Plughub POS Mobile</div>
          <div class="nav-app-sub">One-hand friendly shortcuts</div>
        </div>
        <button class="nav-close" type="button" data-menu-close aria-label="Close menu">&#10005;</button>
      </div>
      <div class="nav-user-chip">
        <div class="nav-avatar" aria-hidden="true"><?= e($avatarInitials) ?></div>
        <div>
          <div class="nav-username"><?= e($usernameDisplay) ?></div>
          <div class="nav-role"><?= e($roleLabel) ?></div>
        </div>
      </div>
      <div class="nav-divider"></div>
      <nav class="nav-groups" aria-label="Primary">
        <div class="nav-group">
          <div class="nav-label">Core operations</div>
          <a class="nav-item" href="/inventory" data-menu-item data-route="/inventory">
            <span class="nav-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" role="presentation">
                <path d="M4 8.5L12 4l8 4.5v7L12 20l-8-4.5v-7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M4 8.5l8 4.5 8-4.5M12 13v7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="nav-text">Inventory</span>
          </a>
          <?php if ($isManager || $isAdmin): ?>
            <a class="nav-item" href="/reports" data-menu-item data-route="/reports">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="presentation">
                  <path d="M5 14.5 10 10l3 2 6-5v10H5v-2.5z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M5 19h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="nav-text">Reports</span>
            </a>
          <?php endif; ?>
          <a class="nav-item" href="/sales-history" data-menu-item data-route="/sales-history">
            <span class="nav-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" role="presentation">
                <path d="M7 4h10v16l-2-1-2 1-2-1-2 1-2-1-2 1V4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M9 8h6M9 11h6M9 14h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="nav-text">Sales History</span>
          </a>
        </div>
        <?php if ($isManager || $isAdmin): ?>
          <div class="nav-divider"></div>
          <div class="nav-group">
            <div class="nav-label">Management</div>
            <?php if ($isAdmin): ?>
              <a class="nav-item is-muted" href="/tenant-config" data-menu-item data-route="/tenant-config">
                <span class="nav-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" role="presentation">
                    <path d="M5 9h14M5 15h14M9 5v14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <span class="nav-text">Tenant Configuration</span>
              </a>
            <?php endif; ?>
            <a class="nav-item is-muted" href="/manage-users" data-menu-item data-route="/manage-users">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="presentation">
                  <path d="M8.5 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM17 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                  <path d="M4 19.5c0-2.2 2.4-3.5 4.5-3.5s4.5 1.3 4.5 3.5M14.5 18.5c.5-.9 1.7-1.5 3-1.5 1.3 0 2.5.6 3 1.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="nav-text">Manage Users</span>
            </a>
            <a class="nav-item is-muted" href="/login" data-menu-item data-route="/login">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="presentation">
                  <path d="M10 6h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M5 12h10M8 9l-3 3 3 3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="nav-text">Switch User</span>
            </a>
          </div>
        <?php endif; ?>
        <div class="nav-divider"></div>
        <div class="nav-group">
          <div class="nav-label">Account &amp; system</div>
          <a class="nav-item is-danger" href="/logout" data-menu-item data-logout-link>
            <span class="nav-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" role="presentation">
                <path d="M13 5h-3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M15 12H4M12 9l3 3-3 3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="nav-text">Logout</span>
            <span class="nav-caret" aria-hidden="true">&gt;</span>
          </a>
        </div>
      </nav>
    </aside>
  </div>

  <div class="confirm-overlay" data-logout-dialog aria-hidden="true">
    <div class="confirm-card" role="alertdialog" aria-modal="true" aria-labelledby="logout-title" aria-describedby="logout-copy">
      <div class="confirm-header">
        <div class="confirm-icon" aria-hidden="true">!</div>
        <div>
          <div class="confirm-title" id="logout-title">Logout?</div>
          <div class="confirm-copy" id="logout-copy">Are you sure you want to log out?</div>
        </div>
      </div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" type="button" data-logout-cancel>Cancel</button>
        <a class="btn btn-danger" href="/logout" data-logout-confirm>Logout</a>
      </div>
    </div>
  </div>

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
    'tenant_address' => $tenantAddress,
    'tenant_contact' => $tenantContact,
    'cashier_name' => $user ? (string) ($user['username'] ?? '') : 'Cashier',
    'cashier_full_name' => $cashierFull,
];
$contextJson = json_encode($ctx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$content = ob_get_clean() . PHP_EOL . '<script>window.__APP_CTX__ = ' . $contextJson . ';</script>';
require __DIR__ . '/layout.php';
