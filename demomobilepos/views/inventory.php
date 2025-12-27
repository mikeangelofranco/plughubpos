<?php
declare(strict_types=1);

ob_start();
$flash = is_string($flash ?? null) ? $flash : null;
$flashError = is_string($flash_error ?? null) ? $flash_error : null;
$tenantName = is_string($tenant_name ?? null) ? $tenant_name : 'All Tenants';
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

<main class="content inventory" data-inventory>
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
  let selectedProduct = null;
  let lastCreatedId = null;
  let lastAdjustedId = null;
  let adjustInitialState = null;
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

  const showModal = (modalEl) => {
    if (!modalEl || !overlay) return;
    overlay.classList.remove('is-hidden');
    modalEl.classList.remove('is-hidden');
    requestAnimationFrame(()=>{
      overlay.classList.add('is-visible');
      modalEl.classList.add('is-visible');
      modalEl.scrollTop = 0;
    });
  };

  const hideModals = () => {
    overlay?.classList.remove('is-visible');
    [addModal, adjustModal].forEach((m)=>m?.classList.remove('is-visible'));
    adjustInitialState = null;
    setTimeout(()=>{
      overlay?.classList.add('is-hidden');
      [addModal, adjustModal].forEach((m)=>m?.classList.add('is-hidden'));
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
      const highlight = isNew || isAdjusted;
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
    if (prod) openAdjustModal(prod, 'add');
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
      if (isAdjustVisible()) {
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
    if (isAdjustVisible()) {
      closeAdjustModal();
    } else {
      hideModals();
    }
  });
  modalCloseBtns.forEach((btn)=>btn.addEventListener('click', (e)=>{
    const withinAdjust = btn.closest('[data-modal-adjust]');
    if (withinAdjust) {
      closeAdjustModal();
    } else {
      hideModals();
    }
  }));
  adjustBackBtn?.addEventListener('click', ()=> closeAdjustModal());
  adjustCloseBtn?.addEventListener('click', ()=> closeAdjustModal());

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
