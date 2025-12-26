<?php
declare(strict_types=1);

ob_start();
$user = is_array($user ?? null) ? $user : null;
$role = is_string($role ?? null) ? $role : (string) ($user['role'] ?? 'Cashier');
$roleLower = strtolower($role);
$tenantId = isset($tenant_id) && is_numeric($tenant_id) ? (int) $tenant_id : null;
$tenantName = is_string($tenant_name ?? null) ? $tenant_name : 'Tenant';
$tenantAddress = is_string($tenant_address ?? null) ? $tenant_address : '';
$tenantContact = is_string($tenant_contact ?? null) ? $tenant_contact : '';
$cashierFull = is_string($user['full_name'] ?? null) ? $user['full_name'] : '';
$usernameDisplay = is_string($user['username'] ?? null) ? $user['username'] : 'Cashier';
?>
<main class="content reports-screen" data-reports data-role="<?= e($roleLower) ?>">
  <section class="panel reports-appbar">
    <div class="appbar-left">
      <a class="btn btn-ghost icon-only" href="/" aria-label="Back" title="Back">←</a>
      <div class="hero-left">
        <div class="hero-title">Reports</div>
        <div class="hero-subtle">Sales summary &amp; items sold</div>
        <div class="hero-tenant">Tenant: <?= e($tenantId === null ? 'All Tenants' : $tenantName) ?></div>
      </div>
    </div>
    <div class="appbar-actions reports-actions">
      <button class="btn btn-ghost icon-only" type="button" title="Help" aria-label="Help" data-report-help>?</button>
      <button class="btn btn-primary btn-elevated" type="button" data-report-download>
        <span class="btn-icon">⇩</span>
        Download
      </button>
    </div>
  </section>

  <section class="panel report-filters">
    <div class="filters-inline">
      <label class="label">
        <span>From</span>
        <input class="input" type="date" data-report-from />
      </label>
      <label class="label">
        <span>To</span>
        <input class="input" type="date" data-report-to />
      </label>
      <div class="filters-actions">
        <button class="btn btn-ghost" type="button" data-report-clear>Clear</button>
        <button class="btn btn-primary" type="button" data-report-apply>Apply</button>
      </div>
    </div>
  </section>

  <section class="panel reports-tabs">
    <div class="tablist" role="tablist" aria-label="Report type">
      <button class="tab active" role="tab" aria-selected="true" data-report-tab="sales">Sales Summary</button>
      <button class="tab" role="tab" aria-selected="false" data-report-tab="items">Items Sold</button>
    </div>
  </section>

  <section class="panel kpi-shell" data-report-kpis>
    <div class="kpi-head">
      <div>
        <div class="eyebrow">Sales Summary</div>
        <div class="kpi-range" data-report-summary-text>All dates</div>
      </div>
      <button class="btn btn-ghost btn-compact kpi-toggle" type="button" data-kpi-toggle aria-expanded="false">
        <span data-kpi-toggle-label>View More Metrics</span>
        <span class="kpi-toggle-icon" aria-hidden="true">+</span>
      </button>
    </div>
    <div class="kpi-grid primary-kpis">
      <article class="kpi-card kpi-primary-card">
        <div class="kpi-label">Total Sales</div>
        <div class="kpi-value" data-kpi-total>₱0.00</div>
        <div class="kpi-trend" data-kpi-period>Waiting for data…</div>
      </article>
      <article class="kpi-card kpi-primary-card">
        <div class="kpi-label">Transactions</div>
        <div class="kpi-value" data-kpi-transactions>0</div>
      </article>
      <article class="kpi-card kpi-primary-card">
        <div class="kpi-label">Items Sold</div>
        <div class="kpi-value" data-kpi-items>0</div>
      </article>
      <article class="kpi-card kpi-primary-card">
        <div class="kpi-label">Avg Basket</div>
        <div class="kpi-value" data-kpi-basket>₱0.00</div>
        <div class="kpi-sub">Per transaction</div>
      </article>
    </div>
    <div class="kpi-secondary-grid" data-kpi-secondary hidden>
      <article class="kpi-card">
        <div class="kpi-label">Avg Items / Sale</div>
        <div class="kpi-value" data-kpi-avg-items>0</div>
      </article>
      <article class="kpi-card">
        <div class="kpi-label">Discounts Given</div>
        <div class="kpi-value" data-kpi-discount>₱0.00</div>
        <div class="kpi-sub" data-kpi-discount-rate>—</div>
      </article>
      <article class="kpi-card">
        <div class="kpi-label">Net vs Gross</div>
        <div class="kpi-value" data-kpi-net>₱0.00</div>
        <div class="kpi-sub">Gross: <span data-kpi-gross>₱0.00</span></div>
      </article>
      <article class="kpi-card kpi-card-wide">
        <div class="kpi-label">Payment Breakdown</div>
        <div class="kpi-breakdown" data-kpi-payments>
          <div class="kpi-breakdown-row"><span>Cash</span><span>₱0.00</span></div>
          <div class="kpi-breakdown-row"><span>QR Code</span><span>₱0.00</span></div>
        </div>
      </article>
      <article class="kpi-card">
        <div class="kpi-label">Top Item</div>
        <div class="kpi-top-name" data-kpi-top-name>—</div>
        <div class="kpi-sub" data-kpi-top-amount>—</div>
        <div class="kpi-meta" data-kpi-top-qty></div>
      </article>
    </div>
  </section>

  <section class="panel report-results" data-report-results>
    <div class="panel-head">
      <div>
        <div class="eyebrow" data-report-tab-label>Items Sold</div>
        <h2>Results</h2>
      </div>
      <div class="results-actions">
        <label class="toggle" data-group-toggle>
          <input type="checkbox" data-aggregate-report checked />
          <span>Aggregate report</span>
        </label>
      </div>
    </div>
    <div class="report-list" data-report-list>
      <div class="skeleton-list" data-report-skeleton>
        <?php for ($i = 0; $i < 6; $i++): ?>
          <div class="skeleton-card"></div>
        <?php endfor; ?>
      </div>
    </div>
    <div class="pagination" data-report-pagination>
      <div class="muted" data-report-pagecopy>Showing 0–0 of 0</div>
      <div class="pagination-actions">
        <button class="btn btn-ghost" type="button" data-report-prev disabled>Prev</button>
        <button class="btn btn-ghost" type="button" data-report-next disabled>Next</button>
      </div>
    </div>
  </section>
</main>

<div class="sheet-overlay" data-download-sheet>
  <div class="sheet-card">
    <div class="sheet-head">
      <div>
        <div class="sheet-title">Download</div>
        <div class="sheet-sub">Exports all rows matching your filters.</div>
      </div>
      <button class="nav-close" type="button" data-download-close aria-label="Close">✕</button>
    </div>
    <div class="sheet-actions">
      <button class="btn btn-primary btn-elevated" type="button" data-download-csv>Download CSV</button>
      <button class="btn btn-ghost" type="button" data-download-xlsx>Download Excel CSV (.csv)</button>
    </div>
    <div class="sheet-note">Includes date range and filters in the filename.</div>
  </div>
</div>

<?php
$ctx = [
    'role' => $role,
    'tenant_id' => $tenantId,
    'tenant_name' => $tenantName,
    'tenant_address' => $tenantAddress,
    'tenant_contact' => $tenantContact,
    'cashier_name' => $usernameDisplay,
    'cashier_full_name' => $cashierFull,
];
$contextJson = json_encode($ctx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$content = ob_get_clean() . PHP_EOL . '<script>window.__APP_CTX__ = ' . $contextJson . ';</script>';
require __DIR__ . '/layout.php';
