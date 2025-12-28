<?php
declare(strict_types=1);

ob_start();
$flash = is_string($flash ?? null) ? $flash : null;
$flashError = is_string($flash_error ?? null) ? $flash_error : null;
$tenantName = is_string($tenant_name ?? null) ? $tenant_name : 'All Tenants';
$role = is_string($role ?? null) ? strtolower($role) : '';
$canChangePrice = in_array($role, ['admin', 'manager'], true);
?>
<header class="topbar inventory-bar">
  <div class="inventory-appbar">
    <button class="btn btn-ghost icon-only" type="button" aria-label="Back" data-inventory-back>←</button>
    <div class="brand-text">
      <div class="brand-name">Products</div>
      <div class="brand-sub">Manage items, categories, stock, and costs</div>
      <div class="brand-tenant">Tenant: <?= e($tenantName) ?></div>
    </div>
    <div class="top-actions inventory-actions">
      <button class="btn btn-primary" type="button" data-inv-add>+ Add Product</button>
    </div>
  </div>
</header>

<style>
  .actions-shell {
    padding: 0 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .actions-appbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 6px 6px;
    gap: 8px;
  }
  .actions-appbar .appbar-title {
    flex: 1;
    text-align: center;
    font-weight: 700;
    font-size: 1.05rem;
    color: #111827;
  }
  .product-identity-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
  }
  .product-thumb {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #eef2ff, #e5e7eb);
    color: #374151;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    letter-spacing: -0.01em;
  }
  .product-identity {
    min-width: 0;
  }
  .product-name {
    font-weight: 700;
    font-size: 1.02rem;
    color: #0f172a;
  }
  .product-sku {
    color: #6b7280;
    font-size: 0.95rem;
  }
  .actions-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
  .stat-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-height: 90px;
  }
  .stat-label {
    color: #6b7280;
    font-weight: 600;
    font-size: 0.95rem;
  }
  .stat-value {
    color: #0f172a;
    font-weight: 800;
    font-size: 1.3rem;
  }
  .stat-sub {
    color: #9ca3af;
    font-size: 0.88rem;
  }
  .actions-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .actions-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .action-tile {
    width: 100%;
    text-align: left;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #fff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 60px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    transition: transform 120ms ease, box-shadow 120ms ease, border-color 120ms ease;
  }
  .action-tile .action-main {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .action-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
  }
  .action-title { font-weight: 700; font-size: 1rem; color: #0f172a; }
  .action-sub { color: #4b5563; font-size: 0.93rem; }
  .action-chevron { color: #9ca3af; font-weight: 700; }
  .action-tile:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    border-color: #d1d5db;
  }
  .action-tile:active {
    transform: translateY(0);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
  }
  .action-tile.primary {
    background: #f3f6fb;
    border-color: #d3ddf6;
  }
  .action-tile.primary .action-icon {
    background: #e0e7ff;
    color: #1d4ed8;
  }
  .action-tile.secondary {
    background: #fff;
    border-color: #d1d5db;
  }
  .action-tile.secondary .action-icon {
    background: #f1f5f9;
    color: #0f172a;
  }
  .action-tile.secondary.outline {
    border-style: dashed;
  }
  .action-tile.is-disabled {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
  }
  .action-tile.is-hidden {
    display: none;
  }
  .action-helper {
    color: #6b7280;
    font-size: 0.92rem;
    line-height: 1.4;
    padding: 0 2px;
  }
  .action-hint {
    color: #b45309;
    font-size: 0.92rem;
    padding: 0 2px;
  }
  .actions-foot {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 0 2px 4px;
  }
  .price-header { align-items: flex-start; }
  .price-product-name {
    color: #6b7280;
    font-size: 0.95rem;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .price-summary-card {
    padding: 14px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
  }
  .price-summary-card .summary-label {
    color: #6b7280;
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.01em;
    text-transform: uppercase;
  }
  .price-summary-card .summary-value {
    color: #0f172a;
    font-weight: 800;
    font-size: 1.6rem;
    letter-spacing: -0.01em;
  }
  .price-summary-card .summary-note {
    color: #6b7280;
    font-size: 0.9rem;
    margin-top: 4px;
  }
  .summary-chip {
    background: #e5e7eb;
    color: #374151;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .price-guard {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid #fcd34d;
    background: #fffbeb;
    margin: 6px 0 2px;
  }
  .guard-pill {
    background: #fbbf24;
    color: #78350f;
    border-radius: 999px;
    padding: 4px 10px;
    font-weight: 800;
    font-size: 0.85rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .guard-copy {
    color: #92400e;
    font-size: 0.94rem;
    line-height: 1.4;
  }
  .price-fieldset {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 12px;
  }
  .label-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
  }
  .price-helper {
    color: #6b7280;
    font-size: 0.9rem;
    min-height: 20px;
  }
  .currency-input {
    position: relative;
    display: flex;
    align-items: center;
  }
  .currency-input .currency-symbol {
    position: absolute;
    left: 12px;
    color: #6b7280;
    font-weight: 800;
    pointer-events: none;
  }
  .currency-input .input {
    padding-left: 34px;
    font-weight: 700;
    font-size: 1.05rem;
  }
  .price-error {
    color: #b91c1c;
    min-height: 18px;
    margin-top: -2px;
  }
  .price-change-card {
    border: 1px dashed #d1d5db;
    border-radius: 12px;
    background: #f9fafb;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .price-change-card.muted { opacity: 0.9; }
  .change-row {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: space-between;
  }
  .change-col {
    flex: 1;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 12px;
    background: #fff;
  }
  .change-label {
    color: #6b7280;
    font-weight: 700;
    font-size: 0.92rem;
    letter-spacing: 0.01em;
  }
  .change-value {
    color: #0f172a;
    font-weight: 800;
    font-size: 1.2rem;
  }
  .change-arrow {
    color: #9ca3af;
    font-weight: 800;
    font-size: 1.2rem;
  }
  .change-delta {
    color: #0f172a;
    font-weight: 700;
    font-size: 0.98rem;
  }
  .change-delta.muted {
    color: #6b7280;
    font-weight: 600;
  }
  .price-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  .price-actions .action-buttons {
    display: flex;
    gap: 8px;
  }
  .price-actions .btn {
    min-width: 120px;
  }
  .action-footnote {
    color: #6b7280;
    font-size: 0.92rem;
    flex: 1;
  }
  @media (max-width: 540px) {
    .change-row { flex-direction: column; align-items: stretch; }
    .change-arrow { display: none; }
    .price-actions { flex-direction: column; align-items: flex-start; }
    .price-actions .action-buttons { width: 100%; justify-content: space-between; }
  }
</style>

<main class="content inventory" data-inventory data-role="<?= e($role) ?>" data-can-change-price="<?= $canChangePrice ? '1' : '0' ?>">
  <?php if ($flash): ?>
    <section class="panel"><div class="notice notice-ok"><?= e($flash) ?></div></section>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <section class="panel"><div class="notice notice-error"><?= e($flashError) ?></div></section>
  <?php endif; ?>

  <section class="panel inventory-summary">
    <div class="summary-chips" data-summary-chips>
      <button class="summary-chip active" data-summary="all">All <span data-summary-count="all">0</span></button>
      <button class="summary-chip" data-summary="low">Low <span data-summary-count="low">0</span></button>
      <button class="summary-chip" data-summary="out">Out <span data-summary-count="out">0</span></button>
    </div>
  </section>

  <section class="panel inventory-filters">
    <div class="chips categories" data-inventory-cats>
      <button class="cat-chip active" data-cat="all">All</button>
    </div>
    <div class="inventory-search">
      <div class="input-wrap input-tall inventory-search-input">
        <span class="input-icon">🔍</span>
        <input class="input" data-inventory-search placeholder="Search name, SKU, or barcode" />
        <button class="input-trailing" type="button" data-inventory-clear aria-label="Clear search">×</button>
        <button class="input-trailing icon" type="button" data-inventory-scan aria-label="Scan barcode">📷</button>
      </div>
      <div class="filter-row">
        <button class="filter-chip" type="button" data-filter="low">Low stock</button>
        <button class="filter-chip" type="button" data-filter="out">Out of stock</button>
        <button class="filter-chip" type="button" data-filter="inactive" aria-disabled="true">Inactive</button>
        <div class="sort-wrap">
          <label class="microcopy">Sort</label>
          <select data-sort class="input input-compact">
            <option value="newest">Newest</option>
            <option value="name">Name (?Z)</option>
            <option value="stock">Stock (Low to High)</option>
            <option value="profit">Profit (High to Low)</option>
          </select>
        </div>
      </div>
    </div>
  </section>

  <section class="panel inventory-list">
    <div class="panel-head">
      <h2>Products</h2>
      <div class="pill" data-inventory-count>Showing 0 of 0</div>
    </div>
    <div class="inventory-cards" data-inventory-list>
      <div class="empty-card">
        <div class="empty-icon">&#128221;</div>
        <div>No products found</div>
        <div class="microcopy">Add a product or adjust filters.</div>
      </div>
    </div>
  </section>
</main>
<div class="modal-overlay is-hidden" data-modal-overlay></div>
<div class="modal sheet is-hidden" data-modal-add>
  <div class="modal-header">
    <div>
      <div class="modal-title">Add Product</div>
      <div class="modal-sub">Create a product, set price/cost, and initial stock.</div>
    </div>
    <button class="btn btn-ghost icon-only" type="button" aria-label="Close" data-modal-close>×</button>
  </div>
  <div class="modal-divider"></div>
  <form class="form add-form" data-add-form>
    <label class="label">
      <span>Product Name <span class="required">*</span></span>
      <input class="input" data-add-name placeholder="e.g. Type-C Fast Charging Cable" autocomplete="off" required />
    </label>
    <label class="label">
      <span>SKU (Optional)</span>
      <input class="input" data-add-sku placeholder="Auto-generated if empty" autocomplete="off" />
    </label>
    <div class="grid-two">
      <label class="label">
        <span>Selling Price (₱) <span class="required">*</span></span>
        <input class="input" type="number" step="0.01" min="0" data-add-price required />
      </label>
      <label class="label">
        <span>Cost (₱)</span>
        <input class="input" type="number" step="0.01" min="0" data-add-cost />
      </label>
    </div>
    <div class="microcopy cost-helper">Used for profit and inventory valuation</div>
    <div class="profit-preview is-hidden" data-profit-preview>Estimated Profit: ₱0.00</div>
    <label class="label">
      <span>Initial Stock</span>
      <div class="input-wrap stepper">
        <button class="btn btn-ghost btn-compact" type="button" data-step="-1">–</button>
        <input class="input" type="number" min="0" step="1" data-add-qty value="0" />
        <button class="btn btn-ghost btn-compact" type="button" data-step="1">+</button>
      </div>
      <div class="microcopy">You can adjust stock later.</div>
    </label>
    <div class="grid-two">
      <label class="label">
        <span>Category</span>
        <select class="input" data-add-cat>
          <option value="">Select category</option>
        </select>
      </label>
      <label class="label">
        <span>New Category</span>
        <input class="input" data-add-newcat placeholder="Optional" autocomplete="off" />
      </label>
    </div>
    <div class="microcopy newcat-hint is-hidden" data-newcat-hint>A new category will be created.</div>
    <div class="microcopy req-note">* Required fields</div>
    <div class="modal-actions sticky-actions">
      <button class="btn btn-ghost" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-primary" type="submit" data-save disabled>Save Product</button>
    </div>
  </form>
</div>
<div class="modal sheet is-hidden" data-modal-actions>
  <div class="modal-header adjust-header actions-appbar">
    <button class="btn btn-ghost icon-only" type="button" aria-label="Back" data-actions-back>←</button>
    <div class="appbar-title">Product Actions</div>
    <button class="btn btn-ghost icon-only" type="button" aria-label="Close" data-modal-close>×</button>
  </div>
  <div class="modal-divider"></div>
  <div class="actions-shell">
    <div class="product-identity-card">
      <div class="product-thumb" data-actions-avatar aria-hidden="true">P</div>
      <div class="product-identity">
        <div class="product-name" data-actions-name>Product</div>
        <div class="product-sku" data-actions-sku>SKU</div>
      </div>
    </div>
    <div class="actions-stats">
      <div class="stat-card">
        <div class="stat-label">Current Stock</div>
        <div class="stat-value" data-actions-stock>0</div>
        <div class="stat-sub">units</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Selling Price</div>
        <div class="stat-value" data-actions-price>₱0.00</div>
        <div class="stat-sub">per unit</div>
      </div>
    </div>
    <div class="actions-section">
      <div class="actions-list">
        <button class="action-tile primary" type="button" data-actions-adjust>
          <div class="action-main">
            <div class="action-icon" aria-hidden="true">±</div>
            <div>
              <div class="action-title">Adjust Stock</div>
              <div class="action-sub">Increase or decrease quantity with reason</div>
            </div>
          </div>
          <span class="action-chevron" aria-hidden="true">›</span>
        </button>
        <button class="action-tile secondary outline" type="button" data-actions-change-price>
          <div class="action-main">
            <div class="action-icon" aria-hidden="true">₱</div>
            <div>
              <div class="action-title">Change Price</div>
              <div class="action-sub" data-actions-price-sub>Update selling price only</div>
            </div>
          </div>
          <span class="action-chevron" aria-hidden="true">›</span>
        </button>
      </div>
    </div>
    <div class="actions-foot">
      <div class="action-helper" data-actions-helper>Actions here affect future transactions only.</div>
      <div class="action-hint is-hidden" data-price-permission-hint>Manager access required.</div>
    </div>
  </div>
</div>
<div class="modal sheet is-hidden" data-modal-price>
  <div class="modal-header adjust-header price-header">
    <button class="btn btn-ghost icon-only" type="button" aria-label="Back" data-price-back>←</button>
    <div class="adjust-heading">
      <div class="modal-title">Change Price</div>
      <div class="modal-sub price-product-name" data-price-name>Product</div>
    </div>
    <button class="btn btn-ghost icon-only" type="button" aria-label="Close" data-price-close>×</button>
  </div>
  <div class="modal-divider"></div>
  <form class="form price-form" data-price-form>
    <div class="price-summary-card">
      <div>
        <div class="summary-label">Current price</div>
        <div class="summary-value" data-price-current>₱0.00</div>
        <div class="summary-note">Read-only · Used for upcoming sales</div>
      </div>
      <div class="summary-chip">Current</div>
    </div>
    <div class="price-guard">
      <div class="guard-pill">Future sales</div>
      <div class="guard-copy">Price updates start on the next sale. Past receipts stay unchanged.</div>
    </div>
    <div class="price-fieldset">
      <label class="label price-label">
        <div class="label-row">
          <span>New Selling Price (₱) <span class="required">*</span></span>
          <span class="price-helper" data-price-helper>Pre-filled with current price.</span>
        </div>
        <div class="currency-input">
          <span class="currency-symbol" aria-hidden="true">₱</span>
          <input class="input" type="number" step="0.01" min="0.01" inputmode="decimal" data-price-input required />
        </div>
      </label>
      <div class="microcopy price-error" data-price-error></div>
    </div>
    <div class="price-change-card" data-price-diff>
      <div class="change-row">
        <div class="change-col">
          <div class="change-label">From</div>
          <div class="change-value" data-price-from>₱0.00</div>
        </div>
        <div class="change-arrow" aria-hidden="true">→</div>
        <div class="change-col">
          <div class="change-label">To</div>
          <div class="change-value" data-price-to>₱0.00</div>
        </div>
      </div>
      <div class="change-delta muted" data-price-delta>Enter a new price to preview changes.</div>
    </div>
    <label class="label">
      <span>Reason (Optional)</span>
      <input class="input" data-price-reason placeholder="e.g. Cost increase, Promo ended, Supplier change" />
    </label>
    <div class="modal-actions sticky-actions price-actions">
      <div class="action-footnote" data-price-footnote>Save activates when price is valid and different.</div>
      <div class="action-buttons">
        <button class="btn btn-ghost" type="button" data-price-cancel>Cancel</button>
        <button class="btn btn-primary" type="submit" data-price-save disabled>Save Price</button>
      </div>
    </div>
  </form>
</div>
<div class="modal sheet adjust-screen is-hidden" data-modal-adjust>
  <div class="modal-header adjust-header">
    <button class="btn btn-ghost icon-only" type="button" aria-label="Back" data-adjust-back>←</button>
    <div class="adjust-heading">
      <div class="modal-title">Adjust Stock</div>
      <div class="modal-sub">Update inventory with reason and quantity.</div>
    </div>
    <button class="btn btn-ghost icon-only" type="button" aria-label="Close" data-adjust-close>×</button>
  </div>
  <div class="modal-divider"></div>
  <form class="form adjust-form" data-adjust-form>
    <div class="adjust-card">
      <div class="adjust-top">
        <div>
          <div class="adjust-name" data-adjust-name>Product</div>
          <div class="adjust-sku" data-adjust-sku>SKU</div>
        </div>
        <div class="status-pill status-out is-hidden" data-adjust-status>Out of stock</div>
      </div>
      <div class="adjust-stock" data-adjust-current>Current Stock: 0</div>
    </div>

    <div class="label">Adjustment Type</div>
    <div class="radio-group column" data-adjust-type>
      <label class="radio-pill full adjust-option" data-adjust-option="add"><input type="radio" name="adjust_type" value="add" checked> <span class="pill-check" aria-hidden="true">✓</span> <span class="pill-label">Add Stock</span></label>
      <div class="microcopy radio-help" data-help-add>Add Stock → Increase available quantity.</div>
      <label class="radio-pill full adjust-option" data-adjust-option="remove"><input type="radio" name="adjust_type" value="remove"> <span class="pill-check" aria-hidden="true">✓</span> <span class="pill-label">Remove Stock</span></label>
      <div class="microcopy radio-help" data-help-remove>Remove Stock → Decrease available quantity.</div>
      <label class="radio-pill full adjust-option" data-adjust-option="correction"><input type="radio" name="adjust_type" value="correction"> <span class="pill-check" aria-hidden="true">✓</span> <span class="pill-label">Stock Correction</span></label>
      <div class="microcopy radio-help" data-help-correction>Stock Correction → Set exact stock count.</div>
    </div>

    <label class="label">
      <span>Quantity</span>
      <div class="input-wrap stepper">
        <button class="btn btn-ghost btn-compact" type="button" data-adjust-step="-1">–</button>
        <input class="input" type="number" min="1" step="1" data-adjust-qty value="1" />
        <button class="btn btn-ghost btn-compact" type="button" data-adjust-step="1">+</button>
      </div>
      <div class="microcopy qty-helper" data-adjust-qty-helper>Add Stock → This will increase stock.</div>
      <div class="microcopy adjust-hint" data-adjust-hint></div>
    </label>

    <label class="label">
      <span>Reason <span class="required">*</span></span>
      <select class="input" data-adjust-reason>
        <option value="">Select reason</option>
        <option>New delivery</option>
        <option>Damaged items</option>
        <option>Expired items</option>
        <option>Manual correction</option>
        <option>Customer return</option>
        <option>Other</option>
      </select>
      <div class="microcopy adjust-error" data-adjust-reason-error></div>
      <div class="microcopy adjust-impact" data-adjust-impact></div>
    </label>

    <label class="label">
      <span>Note (Optional)</span>
      <textarea class="input" rows="2" maxlength="240" data-adjust-note placeholder="Add additional details"></textarea>
    </label>

    <div class="modal-actions sticky-actions">
      <button class="btn btn-ghost" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-primary" type="submit" data-adjust-save disabled>Confirm Adjustment</button>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
$script = <<<'HTML'
<script>
(function(){
  const root = document.querySelector('[data-inventory]');
  if (!root) return;
  const listEl = root.querySelector('[data-inventory-list]');
  const catWrap = root.querySelector('[data-inventory-cats]');
  const searchInput = root.querySelector('[data-inventory-search]');
  const clearBtn = root.querySelector('[data-inventory-clear]');
  const filterBtns = Array.from(root.querySelectorAll('[data-filter]'));
  const sortSelect = root.querySelector('[data-sort]');
  const countEl = root.querySelector('[data-inventory-count]');
  const summaryChips = Array.from(root.querySelectorAll('[data-summary]'));
  const summaryCounts = {
    all: root.querySelector('[data-summary-count="all"]'),
    low: root.querySelector('[data-summary-count="low"]'),
    out: root.querySelector('[data-summary-count="out"]'),
  };
  const toastStack = document.createElement('div');
  toastStack.className = 'toast-stack';
  document.body.appendChild(toastStack);
  const menuWrap = root.querySelector('[data-inventory-menu]');
  const menuToggle = root.querySelector('[data-inventory-menu-toggle]');
  const overlay = document.querySelector('[data-modal-overlay]');
  const addModal = document.querySelector('[data-modal-add]');
  const adjustModal = document.querySelector('[data-modal-adjust]');
  const actionsModal = document.querySelector('[data-modal-actions]');
  const priceModal = document.querySelector('[data-modal-price]');
  const modalCloseBtns = document.querySelectorAll('[data-modal-close]');
  const form = document.querySelector('[data-add-form]');
  const fieldName = document.querySelector('[data-add-name]');
  const fieldSku = document.querySelector('[data-add-sku]');
  const fieldPrice = document.querySelector('[data-add-price]');
  const fieldCost = document.querySelector('[data-add-cost]');
  const fieldQty = document.querySelector('[data-add-qty]');
  const fieldCat = document.querySelector('[data-add-cat]');
  const fieldNewCat = document.querySelector('[data-add-newcat]');
  const addBtn = document.querySelector('[data-inv-add]');
  const profitPreview = document.querySelector('[data-profit-preview]');
  const saveBtn = document.querySelector('[data-save]');
  const stepButtons = Array.from(document.querySelectorAll('[data-step]'));
  const backBtn = document.querySelector('[data-inventory-back]');
  const newCatHint = document.querySelector('[data-newcat-hint]');
  const adjustForm = document.querySelector('[data-adjust-form]');
  const adjustName = document.querySelector('[data-adjust-name]');
  const adjustSku = document.querySelector('[data-adjust-sku]');
  const adjustCurrent = document.querySelector('[data-adjust-current]');
  const adjustStatus = document.querySelector('[data-adjust-status]');
  const adjustQty = document.querySelector('[data-adjust-qty]');
  const adjustStepBtns = Array.from(document.querySelectorAll('[data-adjust-step]'));
  const adjustReason = document.querySelector('[data-adjust-reason]');
  const adjustNote = document.querySelector('[data-adjust-note]');
  const adjustHint = document.querySelector('[data-adjust-hint]');
  const adjustImpact = document.querySelector('[data-adjust-impact]');
  const adjustReasonError = document.querySelector('[data-adjust-reason-error]');
  const adjustSaveBtn = document.querySelector('[data-adjust-save]');
  const adjustTypeRadios = Array.from(document.querySelectorAll('input[name=\"adjust_type\"]'));
  const adjustTypeOptions = Array.from(document.querySelectorAll('[data-adjust-option]'));
  const adjustQtyHelper = document.querySelector('[data-adjust-qty-helper]');
  const adjustBackBtn = document.querySelector('[data-adjust-back]');
  const adjustCloseBtn = document.querySelector('[data-adjust-close]');
  const actionsAvatar = document.querySelector('[data-actions-avatar]');
  const actionsName = document.querySelector('[data-actions-name]');
  const actionsSku = document.querySelector('[data-actions-sku]');
  const actionsStock = document.querySelector('[data-actions-stock]');
  const actionsPrice = document.querySelector('[data-actions-price]');
  const actionsBackBtn = document.querySelector('[data-actions-back]');
  const actionsAdjustBtn = document.querySelector('[data-actions-adjust]');
  const actionsChangePriceBtn = document.querySelector('[data-actions-change-price]');
  const pricePermissionHint = document.querySelector('[data-price-permission-hint]');
  const priceActionSub = document.querySelector('[data-actions-price-sub]');
  const priceForm = document.querySelector('[data-price-form]');
  const priceInput = document.querySelector('[data-price-input]');
  const priceReason = document.querySelector('[data-price-reason]');
  const priceCurrent = document.querySelector('[data-price-current]');
  const priceError = document.querySelector('[data-price-error]');
  const priceHelper = document.querySelector('[data-price-helper]');
  const priceSaveBtn = document.querySelector('[data-price-save]');
  const priceCancelBtn = document.querySelector('[data-price-cancel]');
  const priceBackBtn = document.querySelector('[data-price-back]');
  const priceCloseBtn = document.querySelector('[data-price-close]');
  const priceName = document.querySelector('[data-price-name]');
  const priceFootnote = document.querySelector('[data-price-footnote]');
  const priceFrom = document.querySelector('[data-price-from]');
  const priceTo = document.querySelector('[data-price-to]');
  const priceDelta = document.querySelector('[data-price-delta]');
  const priceDiffCard = document.querySelector('[data-price-diff]');
  const canChangePrice = root?.getAttribute('data-can-change-price') === '1';
  let selectedProduct = null;
  let lastCreatedId = null;
  let lastAdjustedId = null;
  let lastPriceChangedId = null;
  let adjustInitialState = null;
  let priceInitialCents = 0;
  const lowThreshold = 5;
  let products = [];
  let categories = [];
  let activeCat = 'all';

  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]||c));
  const money = (cents) => (Number(cents||0)/100).toLocaleString('en-PH',{style:'currency',currency:'PHP'});
  const formatPeso = (value) => Number(value||0).toLocaleString('en-PH',{style:'currency',currency:'PHP'});
  const showToast = (message, variant = 'info') => {
    const toast = document.createElement('div');
    toast.className = `toast toast-${variant}`;
    toast.textContent = message;
    toastStack.appendChild(toast);
    setTimeout(()=>{ toast.classList.add('show'); }, 10);
    setTimeout(()=> {
      toast.classList.remove('show');
      setTimeout(()=> toast.remove(), 220);
    }, 2800);
  };

  const statusForQty = (qty) => {
    if (qty <= 0) return 'out';
    if (qty <= lowThreshold) return 'low';
    return 'ok';
  };

  const autoSku = (name) => {
    const base = String(name || '')
      .toUpperCase()
      .replace(/[^A-Z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 12);
    const seed = Math.floor(1000 + Math.random() * 9000);
    return (base || 'SKU') + '-' + seed;
  };

  const allModals = [addModal, adjustModal, actionsModal, priceModal];

  const showModal = (modalEl) => {
    if (!modalEl || !overlay) return;
    overlay.classList.remove('is-hidden');
    allModals.forEach((m) => {
      if (m && m !== modalEl) {
        m.classList.remove('is-visible');
        m.classList.add('is-hidden');
      }
    });
    modalEl.classList.remove('is-hidden');
    requestAnimationFrame(()=>{
      overlay.classList.add('is-visible');
      modalEl.classList.add('is-visible');
      modalEl.scrollTop = 0;
    });
  };

  const hideModals = () => {
    overlay?.classList.remove('is-visible');
    allModals.forEach((m)=>m?.classList.remove('is-visible'));
    adjustInitialState = null;
    priceInitialCents = 0;
    setTimeout(()=>{
      overlay?.classList.add('is-hidden');
      allModals.forEach((m)=>m?.classList.add('is-hidden'));
    }, 220);
  };

  const updateProfitPreview = () => {
    if (!profitPreview) return;
    const price = parseFloat(fieldPrice?.value || '0');
    const cost = parseFloat(fieldCost?.value || '0');
    const hasCostInput = (fieldCost?.value || '').trim() !== '';
    if (!Number.isFinite(price) || price <= 0 || !Number.isFinite(cost) || !hasCostInput) {
      profitPreview.classList.add('is-hidden');
      return;
    }
    const profit = price - (Number.isFinite(cost) ? cost : 0);
    profitPreview.textContent = `Estimated Profit: ${formatPeso(profit)}`;
    profitPreview.classList.remove('is-hidden');
  };

  const validateForm = () => {
    const nameOk = !!(fieldName?.value.trim());
    const priceVal = parseFloat(fieldPrice?.value || '0');
    const costRaw = parseFloat(fieldCost?.value || '0');
    const qtyRaw = parseInt(fieldQty?.value || '0', 10);
    const costVal = Number.isFinite(costRaw) ? costRaw : 0;
    const qtyVal = Number.isFinite(qtyRaw) ? qtyRaw : 0;
    const priceOk = Number.isFinite(priceVal) && priceVal > 0;
    const costOk = costVal >= 0;
    const qtyOk = qtyVal >= 0;
    if (saveBtn) {
      saveBtn.disabled = !(nameOk && priceOk && costOk && qtyOk);
    }
  };

  const bumpQty = (delta) => {
    if (!fieldQty) return;
    const current = parseInt(fieldQty.value || '0', 10) || 0;
    const next = Math.max(0, current + delta);
    fieldQty.value = String(next);
    validateForm();
  };

  const getProductById = (id) => products.find((p)=>Number(p.id) === Number(id));
  const refreshSelectedProduct = () => {
    if (!selectedProduct) return;
    const fresh = getProductById(selectedProduct.id);
    if (fresh) {
      selectedProduct = fresh;
    }
  };

  const getSelectedAdjustType = () => {
    const checked = adjustTypeRadios.find((r)=>r.checked);
    return checked ? checked.value : '';
  };

  const setAdjustType = (val) => {
    adjustTypeRadios.forEach((r)=>{ r.checked = r.value === val; });
    syncAdjustTypeUI();
    syncQtyBounds();
  };

  const qtyMinForType = (type) => type === 'correction' ? 0 : 1;

  const syncAdjustTypeUI = () => {
    adjustTypeOptions.forEach((opt)=>{
      const input = opt.querySelector('input[type=\"radio\"]');
      opt.classList.toggle('is-active', !!input?.checked);
    });
  };

  const syncQtyBounds = () => {
    const type = getSelectedAdjustType();
    const min = qtyMinForType(type);
    if (adjustQty) {
      adjustQty.min = String(min);
      const currentVal = parseInt(adjustQty.value || '0', 10);
      const safeVal = Number.isFinite(currentVal) ? currentVal : 0;
      if (safeVal < min) {
        adjustQty.value = String(min);
      }
    }
  };

  const isAdjustVisible = () => adjustModal?.classList.contains('is-visible');
  const isActionsVisible = () => actionsModal?.classList.contains('is-visible');
  const isPriceVisible = () => priceModal?.classList.contains('is-visible');

  const getAdjustState = () => ({
    productId: selectedProduct?.id ?? null,
    type: getSelectedAdjustType(),
    qty: parseInt(adjustQty?.value || '0', 10) || 0,
    reason: (adjustReason?.value || '').trim(),
    note: (adjustNote?.value || '').trim(),
  });

  const setAdjustInitialState = () => { adjustInitialState = getAdjustState(); };

  const isAdjustDirty = () => {
    if (!adjustInitialState) return false;
    const current = getAdjustState();
    return ['productId','type','qty','reason','note'].some((k)=> String(current[k] ?? '') !== String(adjustInitialState[k] ?? ''));
  };

  const closeAdjustModal = (force = false) => {
    if (!isAdjustVisible()) {
      hideModals();
      return true;
    }
    if (!force && isAdjustDirty()) {
      const confirmLeave = window.confirm('Discard unsaved stock adjustment?');
      if (!confirmLeave) return false;
    }
    hideModals();
    return true;
  };

  const computeImpact = () => {
    if (!selectedProduct) return null;
    const type = getSelectedAdjustType();
    const qtyRaw = parseInt(adjustQty?.value || '0', 10);
    const minQty = qtyMinForType(type);
    const qty = Math.max(minQty, Number.isFinite(qtyRaw) ? qtyRaw : 0);
    const current = Number(selectedProduct.qty_on_hand || 0);
    let next = current;
    if (type === 'add') next = current + qty;
    else if (type === 'remove') next = Math.max(0, current - qty);
    else if (type === 'correction') next = qty;
    return { current, next, type, qty };
  };

  const validateAdjust = () => {
    if (!adjustSaveBtn) return;
    const type = getSelectedAdjustType();
    syncAdjustTypeUI();
    syncQtyBounds();
    const qtyParsed = parseInt(adjustQty?.value || '0', 10);
    const qty = Number.isFinite(qtyParsed) ? qtyParsed : 0;
    const minQty = qtyMinForType(type);
    const clampedQty = Math.max(minQty, qty);
    if (adjustQty) adjustQty.value = String(clampedQty);
    const reasonVal = (adjustReason?.value || '').trim();
    const current = Number(selectedProduct?.qty_on_hand || 0);
    let hint = '';
    let ok = !!selectedProduct && type !== '' && clampedQty >= minQty && reasonVal !== '';
    if (type === 'remove' && clampedQty > current) {
      ok = false;
      hint = `Cannot remove more than current stock (${current}).`;
    }
    if (type === 'correction') {
      hint = 'Sets stock to this exact number.';
    }
    if (adjustReasonError) {
      adjustReasonError.textContent = reasonVal === '' ? 'Reason is required for inventory adjustments' : '';
    }
    if (adjustQtyHelper) {
      const helper = type === 'remove'
        ? 'Remove Stock → This will reduce stock.'
        : type === 'correction'
          ? 'Stock Correction → This will set the exact stock value.'
          : 'Add Stock → This will increase stock.';
      adjustQtyHelper.textContent = helper;
    }
    if (adjustImpact) {
      const impact = computeImpact();
      adjustImpact.textContent = impact ? `New Stock After Adjustment: ${impact.next}` : '';
    }
    if (adjustHint) {
      adjustHint.textContent = hint;
      adjustHint.classList.toggle('is-error', hint.startsWith('Cannot'));
    }
    adjustSaveBtn.disabled = !ok;
    return ok;
  };

  const openAdjustModal = (product, presetType = 'add') => {
    if (!product) return;
    selectedProduct = product;
    adjustName.textContent = product.name || 'Product';
    adjustSku.textContent = `SKU: ${product.sku || 'N/A'}`;
    const currentQty = product.qty_on_hand ?? 0;
    adjustCurrent.textContent = `Current Stock: ${currentQty}`;
    if (adjustStatus) {
      const st = statusForQty(currentQty);
      adjustStatus.classList.add('status-pill');
      adjustStatus.classList.toggle('status-out', st === 'out');
      adjustStatus.classList.toggle('status-low', st === 'low');
      adjustStatus.classList.toggle('status-ok', st === 'ok');
      adjustStatus.textContent = st === 'out' ? 'Out of stock' : st === 'low' ? 'Low stock' : 'Active';
      adjustStatus.classList.toggle('is-hidden', st === 'ok');
    }
    setAdjustType(presetType);
    if (adjustQty) {
      const baseQty = presetType === 'correction' ? Math.max(0, Number(product.qty_on_hand || 0)) : 1;
      adjustQty.value = String(baseQty);
    }
    if (adjustReason) adjustReason.value = '';
    if (adjustNote) adjustNote.value = '';
    if (adjustHint) adjustHint.textContent = '';
    if (adjustReasonError) adjustReasonError.textContent = '';
    if (adjustImpact) adjustImpact.textContent = '';
    validateAdjust();
    setAdjustInitialState();
    showModal(adjustModal);
    adjustModal?.scrollTo({ top: 0, behavior: 'auto' });
    adjustForm?.scrollTo?.({ top: 0, behavior: 'auto' });
    window.scrollTo({ top: 0, behavior: 'auto' });
  };

  const openActionsModal = (product) => {
    if (!product) return;
    selectedProduct = product;
    const stock = Number(product.qty_on_hand || 0);
    const stockDisplay = Number.isFinite(stock) ? stock.toLocaleString('en-PH') : '0';
    if (actionsName) actionsName.textContent = product.name || 'Product';
    if (actionsSku) actionsSku.textContent = product.sku ? `SKU: ${product.sku}` : 'SKU: N/A';
    if (actionsStock) actionsStock.textContent = stockDisplay;
    if (actionsPrice) actionsPrice.textContent = money(product.price_cents || 0);
    if (actionsAvatar) {
      const initial = (product.name || 'P').trim().charAt(0).toUpperCase() || 'P';
      actionsAvatar.textContent = initial;
    }
    const blocked = !canChangePrice;
    if (actionsChangePriceBtn) {
      actionsChangePriceBtn.disabled = blocked;
      actionsChangePriceBtn.classList.toggle('is-disabled', blocked);
      actionsChangePriceBtn.classList.toggle('is-hidden', blocked);
    }
    if (pricePermissionHint) pricePermissionHint.classList.toggle('is-hidden', !blocked);
    if (priceActionSub) priceActionSub.textContent = blocked ? 'Manager access required' : 'Update selling price only';
    showModal(actionsModal);
  };

  const resetPriceForm = () => {
    priceInitialCents = 0;
    if (priceInput) priceInput.value = '';
    if (priceReason) priceReason.value = '';
    if (priceError) priceError.textContent = '';
    if (priceHelper) priceHelper.textContent = 'Pre-filled with current price.';
    if (priceFootnote) priceFootnote.textContent = 'Save activates when price is valid and different.';
    if (priceFrom) priceFrom.textContent = money(priceInitialCents);
    if (priceTo) priceTo.textContent = money(priceInitialCents);
    if (priceDelta) {
      priceDelta.textContent = 'Enter a new price to preview changes.';
      priceDelta.classList.add('muted');
    }
    priceDiffCard?.classList.add('muted');
    if (priceSaveBtn) priceSaveBtn.disabled = true;
  };

  const updatePricePreview = (rawVal, validNumber, changed) => {
    if (!priceDiffCard) return;
    const current = priceInitialCents / 100;
    const nextVal = Number.isFinite(rawVal) ? rawVal : current;
    if (priceFrom) priceFrom.textContent = money(priceInitialCents);
    if (priceTo) priceTo.textContent = money(Math.round(Math.max(0, nextVal * 100)));
    if (!validNumber) {
      if (priceDelta) {
        priceDelta.textContent = 'Enter a valid price to preview changes.';
        priceDelta.classList.add('muted');
      }
      priceDiffCard.classList.add('muted');
      return;
    }
    if (!changed) {
      if (priceDelta) {
        priceDelta.textContent = 'No changes to save.';
        priceDelta.classList.add('muted');
      }
      priceDiffCard.classList.add('muted');
      return;
    }
    const diff = nextVal - current;
    if (priceDelta) {
      const direction = diff > 0 ? 'increase' : 'decrease';
      const diffText = formatPeso(Math.abs(diff));
      priceDelta.textContent = `${diff > 0 ? '+' : '-'}${diffText} ${direction} from current`;
      priceDelta.classList.remove('muted');
    }
    priceDiffCard.classList.remove('muted');
  };

  const validatePriceForm = () => {
    const raw = parseFloat(priceInput?.value || '0');
    const cents = Math.round(raw * 100);
    const validNumber = Number.isFinite(raw) && raw >= 0.01;
    const changed = validNumber && cents !== priceInitialCents;
    if (priceError) {
      priceError.textContent = validNumber ? '' : 'Enter a valid price.';
    }
    if (priceHelper) {
      if (!validNumber) {
        priceHelper.textContent = 'Enter a valid price to continue.';
      } else if (!changed) {
        priceHelper.textContent = 'No changes to save.';
      } else {
        priceHelper.textContent = 'Ready to save. Applies to future sales only.';
      }
    }
    if (priceFootnote) {
      priceFootnote.textContent = changed
        ? 'Save will update the selling price for future transactions.'
        : 'Save activates when price is valid and different.';
    }
    if (priceSaveBtn) priceSaveBtn.disabled = !(validNumber && changed);
    updatePricePreview(raw, validNumber, changed);
    return validNumber && changed;
  };

  const closePriceModal = (toActions = false) => {
    resetPriceForm();
    priceModal?.classList.remove('is-visible');
    priceModal?.classList.add('is-hidden');
    if (toActions && selectedProduct) {
      openActionsModal(selectedProduct);
    } else if (!isAdjustVisible() && !isActionsVisible()) {
      hideModals();
    }
  };

  const openPriceModal = () => {
    if (!selectedProduct) return;
    const priceCents = Number(selectedProduct.price_cents || 0);
    priceInitialCents = priceCents;
    if (priceName) {
      const productName = selectedProduct.name || 'Product';
      priceName.textContent = productName;
      priceName.setAttribute('title', productName);
    }
    if (priceCurrent) priceCurrent.textContent = money(priceCents);
    if (priceInput) {
      priceInput.value = (priceCents / 100).toFixed(2);
      priceInput.focus();
      priceInput.select?.();
    }
    if (priceReason) priceReason.value = '';
    validatePriceForm();
    showModal(priceModal);
  };

  const getCatChips = () => Array.from(catWrap?.querySelectorAll('[data-cat]') || []);
  const setActiveCat = (catId) => {
    activeCat = String(catId);
    getCatChips().forEach((c) => {
      const id = c.getAttribute('data-cat') || '';
      c.classList.toggle('active', String(id) === String(activeCat));
    });
  };

  const renderCategories = () => {
    if (!catWrap) return;
    const chips = getCatChips();
    chips.forEach((c, idx) => { if (idx > 0) c.remove(); });
    categories.forEach((c) => {
      const btn = document.createElement('button');
      btn.className = 'cat-chip';
      btn.setAttribute('data-cat', c.id);
      btn.textContent = c.name;
      catWrap.appendChild(btn);
    });
    getCatChips().forEach((chip) => {
      chip.addEventListener('click', () => {
        setActiveCat(chip.getAttribute('data-cat') || 'all');
        applyFilters();
      });
    });
    setActiveCat(activeCat);
  };

  const applyFilters = () => {
    const q = (searchInput?.value || '').toLowerCase().trim();
    const filters = new Set(filterBtns.filter((b)=>b.classList.contains('active')).map((b)=>b.getAttribute('data-filter')));
    const sort = sortSelect?.value || 'newest';
    let rows = products.slice();

    rows = rows.filter((p) => {
      const name = String(p.name||'').toLowerCase();
      const sku = String(p.sku||'').toLowerCase();
      const catMatch = activeCat === 'all' ? true : String(p.category_id||'') === String(activeCat);
      const searchMatch = !q || name.includes(q) || sku.includes(q);
      const status = statusForQty(Number(p.qty_on_hand||0));
      if (filters.has('low') && status !== 'low') return false;
      if (filters.has('out') && status !== 'out') return false;
      // inactive not available; skip
      return catMatch && searchMatch;
    });

    if (sort === 'name') {
      rows.sort((a,b)=>String(a.name||'').localeCompare(String(b.name||'')));
    } else if (sort === 'stock') {
      rows.sort((a,b)=>Number(a.qty_on_hand||0)-Number(b.qty_on_hand||0));
    } else if (sort === 'profit') {
      rows.sort((a,b)=>{
        const pa = Number(a.price_cents||0)-Number(a.cost_cents||0);
        const pb = Number(b.price_cents||0)-Number(b.cost_cents||0);
        return pb - pa;
      });
    } // newest default order from API

    renderList(rows);
    if (countEl) countEl.textContent = `Showing ${rows.length} of ${products.length}`;
  };

  const renderStatusPill = (status) => {
    if (status === 'out') return '<span class="status-pill status-out">Out of stock</span>';
    if (status === 'low') return '<span class="status-pill status-low">Low stock</span>';
    return '<span class="status-pill status-ok">Active</span>';
  };

  const renderList = (rows) => {
    if (!listEl) return;
    listEl.innerHTML = '';
    if (!rows.length) {
      const card = document.createElement('div');
      card.className = 'empty-card';
      card.innerHTML = "<div class=\"empty-icon\">&#128221;</div><div>No products found</div><div class=\"microcopy\">Add a product or adjust filters.</div>";
      listEl.appendChild(card);
      return;
    }
    const frag = document.createDocumentFragment();
    rows.forEach((p) => {
      const qty = Number(p.qty_on_hand||0);
      const status = statusForQty(qty);
      const profit = Number(p.price_cents||0) - Number(p.cost_cents||0);
      const card = document.createElement('article');
      const isNew = lastCreatedId !== null && Number(lastCreatedId) === Number(p.id);
      const isAdjusted = lastAdjustedId !== null && Number(lastAdjustedId) === Number(p.id);
      const isPriceChanged = lastPriceChangedId !== null && Number(lastPriceChangedId) === Number(p.id);
      const highlight = isNew || isAdjusted || isPriceChanged;
      card.className = 'inventory-card' + (highlight ? ' highlight' : '');
      card.setAttribute('data-row','');
      card.setAttribute('data-id', String(p.id));
      card.setAttribute('data-cat', String(p.category_id||''));
      card.innerHTML = `
        <div class="card-top">
          <div class="card-main">
            <div class="card-row">
              <div class="card-title">${escapeHtml(p.name||'')}</div>
              ${renderStatusPill(status)}
            </div>
            <div class="card-row meta-row">
              <span class="meta">${escapeHtml(p.sku||'')}</span>
            </div>
          </div>
          <div class="card-actions">
            <button class="btn btn-ghost btn-compact" type="button" data-quick-add="${p.id}">+ Stock</button>
          </div>
        </div>
        <div class="metrics-grid">
          <div class="metric"><div class="label">Price</div><div class="value">${money(p.price_cents||0)}</div></div>
          <div class="metric"><div class="label">Cost</div><div class="value muted">${money(p.cost_cents||0)}</div></div>
          <div class="metric"><div class="label">Stock</div><div class="value">${qty}</div></div>
        </div>
        <div class="profit-line">Profit: ${money(profit)}</div>
      `;
      frag.appendChild(card);
      if (highlight) {
        setTimeout(()=> card.classList.remove('highlight'), 2200);
        if (isNew) lastCreatedId = null;
        if (isAdjusted) lastAdjustedId = null;
        if (isPriceChanged) lastPriceChangedId = null;
      }
    });
    listEl.appendChild(frag);
  };

  const updateSummary = () => {
    const counts = { all: products.length, low:0, out:0 };
    products.forEach((p)=>{
      const status = statusForQty(Number(p.qty_on_hand||0));
      if (status === 'low') counts.low++;
      if (status === 'out') counts.out++;
    });
    if (summaryCounts.all) summaryCounts.all.textContent = counts.all;
    if (summaryCounts.low) summaryCounts.low.textContent = counts.low;
    if (summaryCounts.out) summaryCounts.out.textContent = counts.out;
  };

  const activateSummary = (key) => {
    summaryChips.forEach((chip)=>chip.classList.toggle('active', chip.getAttribute('data-summary')===key));
    filterBtns.forEach((b)=>b.classList.remove('active'));
    if (key === 'low') root.querySelector('[data-filter="low"]')?.classList.add('active');
    if (key === 'out') root.querySelector('[data-filter="out"]')?.classList.add('active');
    applyFilters();
  };

  summaryChips.forEach((chip)=>{
    chip.addEventListener('click', ()=>{
      activateSummary(chip.getAttribute('data-summary')||'all');
    });
  });

  clearBtn?.addEventListener('click', ()=>{
    if (searchInput) {
      searchInput.value = '';
      applyFilters();
    }
  });
  searchInput?.addEventListener('input', applyFilters);
  filterBtns.forEach((btn)=>{
    btn.addEventListener('click', ()=>{
      btn.classList.toggle('active');
      applyFilters();
    });
  });
  sortSelect?.addEventListener('change', applyFilters);

  backBtn?.addEventListener('click', ()=>{
    if (window.history.length > 1) {
      window.history.back();
    } else {
      window.location.href = '/';
    }
  });

  [fieldName, fieldPrice, fieldCost, fieldQty].forEach((el)=>{
    el?.addEventListener('input', ()=>{
      updateProfitPreview();
      validateForm();
    });
  });
  fieldNewCat?.addEventListener('input', ()=>{
    const has = (fieldNewCat.value || "").trim() !== "";
    newCatHint?.classList.toggle('is-hidden', !has);
  });

  adjustStepBtns.forEach((btn)=>{
    btn.addEventListener('click', ()=>{
      const delta = parseInt(btn.getAttribute('data-adjust-step') || '0', 10) || 0;
      const type = getSelectedAdjustType();
      const min = qtyMinForType(type);
      const current = parseInt(adjustQty?.value || '0', 10) || 0;
      const next = Math.max(min, current + delta);
      if (adjustQty) adjustQty.value = String(next);
      validateAdjust();
    });
  });
  adjustQty?.addEventListener('input', validateAdjust);
  adjustReason?.addEventListener('change', validateAdjust);
  adjustTypeRadios.forEach((r)=>r.addEventListener('change', ()=>{
    validateAdjust();
  }));
  priceInput?.addEventListener('input', validatePriceForm);
  priceReason?.addEventListener('input', validatePriceForm);
  stepButtons.forEach((btn)=>{
    btn.addEventListener('click', ()=>{
      const delta = parseInt(btn.getAttribute('data-step') || '0', 10) || 0;
      bumpQty(delta);
    });
  });

  validateForm();

  listEl?.addEventListener('click', (e)=>{
    const target = e.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.hasAttribute('data-quick-add')) {
      e.stopPropagation();
      const id = target.getAttribute('data-quick-add');
      const prod = getProductById(id);
      if (prod) openAdjustModal(prod, 'add');
      return;
    }
    const card = target.closest('[data-row]');
    if (!card) return;
    const id = card.getAttribute('data-id');
    const prod = getProductById(id);
    if (prod) openActionsModal(prod);
  });

  const closeMenu = () => menuWrap?.classList.remove('is-open');
  if (menuToggle) {
    menuToggle.addEventListener('click', (e)=>{
      e.preventDefault();
      menuWrap?.classList.toggle('is-open');
    });
    document.addEventListener('click', (e)=>{
      const t = e.target;
      if (!(t instanceof HTMLElement)) return;
      if (menuWrap && menuWrap.contains(t)) return;
      closeMenu();
    });
    document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') closeMenu(); });
  }

  document.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape') {
      closeMenu();
      if (isPriceVisible()) {
        closePriceModal(false);
      } else if (isAdjustVisible()) {
        closeAdjustModal();
      } else {
        hideModals();
      }
    }
  });

  const openAddModal = () => {
    showModal(addModal);
    form?.reset();
    if (fieldCat) {
      fieldCat.innerHTML = '<option value=\"\">Select category</option>';
      categories.forEach((c) => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.name;
        fieldCat.appendChild(o);
      });
      if (activeCat !== 'all') {
        fieldCat.value = String(activeCat);
      }
    }
    if (fieldNewCat) fieldNewCat.value = '';
    if (fieldQty) fieldQty.value = '0';
    newCatHint?.classList.add('is-hidden');
    profitPreview?.classList.add('is-hidden');
    updateProfitPreview();
    validateForm();
    fieldName?.focus();
  };

  addBtn?.addEventListener('click', openAddModal);
  overlay?.addEventListener('click', ()=>{
    if (isPriceVisible()) {
      closePriceModal(false);
    } else if (isAdjustVisible()) {
      closeAdjustModal();
    } else {
      hideModals();
    }
  });
  modalCloseBtns.forEach((btn)=>btn.addEventListener('click', ()=>{
    if (btn.closest('[data-modal-price]')) {
      closePriceModal(false);
    } else if (btn.closest('[data-modal-adjust]')) {
      closeAdjustModal();
    } else {
      hideModals();
    }
  }));
  adjustBackBtn?.addEventListener('click', ()=> closeAdjustModal());
  adjustCloseBtn?.addEventListener('click', ()=> closeAdjustModal());
  actionsBackBtn?.addEventListener('click', ()=> hideModals());
  actionsAdjustBtn?.addEventListener('click', ()=>{
    if (selectedProduct) {
      openAdjustModal(selectedProduct, 'add');
    }
  });
  actionsChangePriceBtn?.addEventListener('click', ()=>{
    if (!selectedProduct) return;
    if (!canChangePrice) {
      showToast("You don't have permission to change prices.", 'error');
      return;
    }
    openPriceModal();
  });
  priceBackBtn?.addEventListener('click', ()=> closePriceModal(true));
  priceCloseBtn?.addEventListener('click', ()=> closePriceModal(false));
  priceCancelBtn?.addEventListener('click', ()=> closePriceModal(true));

  const createCategory = async (name) => {
    const res = await fetch('/api/inventory/categories', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name }),
    });
    const data = await res.json().catch(()=> ({}));
    if (!res.ok || !data?.ok || !data.category) {
      throw new Error(data?.error || 'Could not create category');
    }
    return data.category;
  };

  const createProduct = async (payload) => {
    const res = await fetch('/api/inventory/products', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(()=> ({}));
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Could not create product');
    }
    return data;
  };

  const adjustStock = async (payload) => {
    const res = await fetch('/api/inventory/adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(()=> ({}));
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Could not adjust stock');
    }
    return data;
  };

  const changePrice = async (payload) => {
    const res = await fetch(`/api/products/${payload.product_id}/price`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(()=> ({}));
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Could not update price');
    }
    return data;
  };

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (saveBtn) {
      saveBtn.disabled = true;
      saveBtn.dataset.label = saveBtn.textContent || 'Save Product';
      saveBtn.textContent = 'Saving…';
    }
    const inputs = form?.querySelectorAll('input, select, button, textarea') || [];
    inputs.forEach((el)=>{ if (el !== saveBtn) el.disabled = true; });
    const name = fieldName?.value.trim() || '';
    const rawSku = fieldSku?.value.trim() || '';
    const price = parseFloat(fieldPrice?.value || '0');
    const costVal = parseFloat(fieldCost?.value || '0');
    const cost = Number.isFinite(costVal) ? costVal : 0;
    const qtyParsed = parseInt(fieldQty?.value || '0', 10);
    const qty = Number.isFinite(qtyParsed) ? qtyParsed : 0;
    let categoryId = fieldCat?.value ? parseInt(fieldCat.value, 10) : null;
    const newCatName = fieldNewCat?.value.trim() || '';
    if (!name || !Number.isFinite(price) || price <= 0) {
      showToast('Product name and a price greater than 0 are required.', 'error');
      inputs.forEach((el)=>{ el.disabled = false; });
      if (saveBtn) {
        saveBtn.textContent = saveBtn.dataset.label || 'Save Product';
        saveBtn.disabled = false;
      }
      return;
    }
    const finalSku = rawSku !== '' ? rawSku : autoSku(name);
    try {
      if (newCatName) {
        const c = await createCategory(newCatName);
        if (c?.id) {
          categoryId = c.id;
          categories.push({ id: c.id, name: c.name });
          renderCategories();
          setActiveCat(String(c.id));
          if (fieldCat) fieldCat.value = String(c.id);
        }
      }
      const created = await createProduct({
        name,
        sku: finalSku,
        price_cents: Math.round(Math.max(0, price * 100)),
        cost_cents: Math.round(Math.max(0, cost * 100)),
        qty_on_hand: Math.max(0, qty),
        category_id: categoryId,
      });
      lastCreatedId = created?.product_id || null;
      showToast('Product added successfully.', 'success');
      hideModals();
      fetchAll();
    } catch (err) {
      showToast(err?.message || 'Could not save product.', 'error');
    } finally {
      inputs.forEach((el)=>{ el.disabled = false; });
      if (saveBtn) {
        saveBtn.textContent = saveBtn.dataset.label || 'Save Product';
        validateForm();
      }
    }
  });

  adjustForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!selectedProduct) {
      showToast('Select a product first.', 'error');
      return;
    }
    const type = getSelectedAdjustType();
    const qty = parseInt(adjustQty?.value || '0', 10) || 0;
    const reasonVal = (adjustReason?.value || '').trim();
    const noteVal = (adjustNote?.value || '').trim();
    const current = Number(selectedProduct.qty_on_hand || 0);
    if (!validateAdjust()) return;
    if (type === 'remove' && qty > current) {
      showToast('Cannot remove more than current stock.', 'error');
      return;
    }
    const btn = adjustSaveBtn;
    const inputs = adjustModal?.querySelectorAll('input, select, button') || [];
    if (btn) {
      btn.disabled = true;
      btn.dataset.label = btn.textContent || 'Confirm Adjustment';
      btn.textContent = 'Updating…';
    }
    inputs.forEach((el)=>{ if (el !== btn) el.disabled = true; });
    try {
      const res = await adjustStock({
        product_id: selectedProduct.id,
        adjustment_type: type,
        quantity: qty,
        reason: reasonVal,
        note: noteVal,
      });
      const updated = res.product || { ...selectedProduct, qty_on_hand: res?.movement?.new_stock ?? current };
      products = products.map((p)=> Number(p.id) === Number(updated.id) ? { ...p, ...updated } : p);
      refreshSelectedProduct();
      lastAdjustedId = updated.id;
      updateSummary();
      applyFilters();
      showToast('Stock updated successfully.', 'success');
      closeAdjustModal(true);
    } catch (err) {
      showToast(err?.message || 'Could not adjust stock.', 'error');
    } finally {
      inputs.forEach((el)=>{ el.disabled = false; });
      if (btn) {
        btn.textContent = btn.dataset.label || 'Confirm Adjustment';
        validateAdjust();
      }
    }
  });

  priceForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!selectedProduct) {
      showToast('Select a product first.', 'error');
      return;
    }
    if (!canChangePrice) {
      showToast("You don't have permission to change prices.", 'error');
      return;
    }
    if (!validatePriceForm()) {
      return;
    }
    const newPriceVal = parseFloat(priceInput?.value || '0');
    const reasonVal = (priceReason?.value || '').trim();
    const btn = priceSaveBtn;
    const inputs = priceModal?.querySelectorAll('input, button') || [];
    if (btn) {
      btn.disabled = true;
      btn.dataset.label = btn.textContent || 'Save Price';
      btn.textContent = 'Saving…';
    }
    inputs.forEach((el)=>{ if (el !== btn) el.disabled = true; });
    try {
      const res = await changePrice({
        product_id: selectedProduct.id,
        new_price: newPriceVal,
        reason: reasonVal,
      });
      const updated = res.product || { ...selectedProduct, price_cents: Math.round(newPriceVal * 100) };
      products = products.map((p)=> Number(p.id) === Number(updated.id) ? { ...p, ...updated } : p);
      refreshSelectedProduct();
      lastPriceChangedId = updated.id;
      updateSummary();
      applyFilters();
      showToast('Selling price updated', 'success');
      closePriceModal(true);
    } catch (err) {
      showToast(err?.message || 'Could not update price.', 'error');
    } finally {
      inputs.forEach((el)=>{ el.disabled = false; });
      if (btn) {
        btn.textContent = btn.dataset.label || 'Save Price';
        validatePriceForm();
      }
    }
  });

  const fetchAll = async () => {
    try {
      const [cRes, pRes] = await Promise.all([
        fetch('/api/categories'),
        fetch('/api/products'),
      ]);
      if (cRes.ok) {
        const data = await cRes.json();
        if (data?.ok && Array.isArray(data.categories)) {
          categories = data.categories.map((c)=>({id:c.id, name:c.name}));
          renderCategories();
        }
      }
      if (pRes.ok) {
        const data = await pRes.json();
        if (data?.ok && Array.isArray(data.products)) {
          products = data.products;
          refreshSelectedProduct();
          updateSummary();
          applyFilters();
        }
      }
    } catch (e) {
      console.warn('Inventory load failed', e);
      showToast('Could not load inventory. Please retry.', 'error');
    }
  };

  fetchAll();
})();
</script>
HTML;
$content .= $script;
require __DIR__ . '/layout.php';
