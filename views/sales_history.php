<?php
declare(strict_types=1);

ob_start();
$user = is_array($user ?? null) ? $user : null;
$role = is_string($role ?? null) ? $role : (string) ($user['role'] ?? 'Cashier');
$tenantId = isset($tenant_id) && is_numeric($tenant_id) ? (int) $tenant_id : null;
$tenantName = is_string($tenant_name ?? null) ? $tenant_name : 'All Tenants';
$tenantAddress = is_string($tenant_address ?? null) ? $tenant_address : '';
$tenantContact = is_string($tenant_contact ?? null) ? $tenant_contact : '';
$cashierFull = is_string($user['full_name'] ?? null) ? $user['full_name'] : '';
?>
<main class="content sales-history" data-sales-history>
  <section class="panel sales-hero sales-appbar">
    <div class="appbar-left">
      <a class="btn btn-ghost icon-only" href="/" aria-label="Back" title="Back">←</a>
      <div class="hero-left">
        <div class="hero-title">Sales History</div>
        <div class="hero-subtle">Receipts &amp; Reprints</div>
        <div class="hero-tenant">Tenant: <?= e($tenantId === null ? 'All Tenants' : $tenantName) ?></div>
      </div>
    </div>
    <div class="appbar-actions">
      <div class="status-line muted">Last updated: <span data-sales-lastref>just now</span></div>
      <div class="menu-wrap" data-sales-menu>
        <button class="btn btn-ghost icon-only" type="button" aria-label="More" title="More options" data-sales-menu-toggle>⋯</button>
        <div class="menu-dropdown" data-sales-menu-dropdown>
          <a href="/logout" data-sales-signout>Sign out</a>
        </div>
      </div>
    </div>
  </section>

  <section class="panel status-meta">
    <div class="status-line">
      <span class="muted">Showing:</span>
      <strong>Latest 50 paid sales</strong>
    </div>
    <div class="status-line muted">Need a receipt? Search and reprint instantly.</div>
  </section>

  <section class="panel sales-search">
    <div class="panel-head">
      <div>
        <div class="eyebrow">Find a Sale</div>
        <div class="microcopy">Search by transaction ID, receipt no., or cashier</div>
      </div>
      <div class="pill" data-sales-scope><?= e($tenantId === null ? 'All tenants' : 'Tenant ' . $tenantName) ?></div>
    </div>
    <div class="form form-inline">
      <label class="label">
        <span>Search</span>
        <div class="input-wrap input-tall">
          <span class="input-icon">🔎</span>
          <input class="input" data-sales-query placeholder="Transaction ID / Receipt No. / Cashier" />
        </div>
      </label>
      <div class="actions-row">
        <button class="btn btn-primary" type="button" data-sales-search>Search Sale</button>
        <button class="btn btn-ghost" type="button" data-sales-refresh>Recent Sales</button>
      </div>
      <div class="microcopy">Enter a transaction ID, receipt number, or cashier name then press Enter or Search.</div>
    </div>
  </section>

  <section class="panel sales-results">
    <div class="panel-head">
      <div>
        <div class="eyebrow">Recent Sales</div>
        <h2>Sales list</h2>
      </div>
      <div class="pill pill-count" data-sales-count>0</div>
    </div>
    <div class="sales-list" data-sales-list>
      <div class="empty-card">
        <div class="empty-icon">🧾</div>
        <div>No sales found</div>
        <div class="microcopy">Try searching by transaction ID, receipt number, or cashier.</div>
      </div>
    </div>
  </section>
</main>
<?php
$ctx = [
    'role' => $role,
    'tenant_id' => $tenantId,
    'tenant_name' => $tenantName,
    'tenant_address' => $tenantAddress,
    'tenant_contact' => $tenantContact,
    'cashier_name' => $user ? (string) ($user['username'] ?? 'Cashier') : 'Cashier',
    'cashier_full_name' => $cashierFull,
];
$contextJson = json_encode($ctx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$content = ob_get_clean() . PHP_EOL . '<script>window.__APP_CTX__ = ' . $contextJson . ';</script>';
require __DIR__ . '/layout.php';
