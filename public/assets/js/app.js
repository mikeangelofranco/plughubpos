(() => {
  const ctx = window.__APP_CTX__ || {};
  const ctxRole = String(ctx.role || "");
  const isReadonly = Boolean(ctx.is_readonly) || ctxRole.toLowerCase() === "readonly";
  const tenantName = String(ctx.tenant_name || "Tenant");
  const tenantAddress = String(ctx.tenant_address || "");
  const tenantContact = String(ctx.tenant_contact || "");
  const cashierUser = String(ctx.cashier_name || "Cashier");
  const cashierFull = String(ctx.cashier_full_name || "");
  const currencyCode = String(ctx.currency || ctx.tenant_currency || "PHP").toUpperCase();
  const currencyLocale = ctx.currency_locale || (currencyCode === "PHP" ? "en-PH" : undefined);

  const formatCashierName = () => {
    const raw = cashierFull.trim();
    if (!raw) return cashierUser;
    const parts = raw.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0];
    if (parts.length === 2) return parts[0];
    return parts.slice(0, 2).join(" ");
  };
  const cashierName = formatCashierName();

  const money = (cents) =>
    (Number(cents ?? 0) / 100).toLocaleString(currencyLocale || undefined, {
      style: "currency",
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const toastStack = document.createElement("div");
  toastStack.className = "toast-stack";
  document.body.appendChild(toastStack);
  const showToast = (message, variant = "info") => {
    const toast = document.createElement("div");
    toast.className = `toast toast-${variant}`;
    toast.textContent = message;
    toastStack.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("show"));
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 200);
    }, 2600);
  };

  const fallbackProducts = [
    { id: 1, name: "Coke 50cl", price_cents: 45000, sku: "COKE-50", category_id: 1, qty_on_hand: 32 },
    { id: 2, name: "Bottled Water", price_cents: 25000, sku: "WATER-50", category_id: 1, qty_on_hand: 12 },
    { id: 3, name: "Chin-Chin", price_cents: 60000, sku: "SNACK-01", category_id: 3, qty_on_hand: 6 },
    { id: 4, name: "Bread (Small)", price_cents: 70000, sku: "BREAD-S", category_id: 2, qty_on_hand: 3 },
    { id: 5, name: "Milk 1L", price_cents: 180000, sku: "MILK-1L", category_id: 4, qty_on_hand: 18 },
    { id: 6, name: "Sugar 500g", price_cents: 90000, sku: "SUGAR-500", category_id: 4, qty_on_hand: 0 },
  ];
  const fallbackCategories = [
    { id: "all", name: "All" },
    { id: 1, name: "Beverages" },
    { id: 2, name: "Bakery" },
    { id: 3, name: "Snacks" },
    { id: 4, name: "Essentials" },
  ];

  const state = {
    cart: new Map(), // productId -> qty
    query: "",
    categoryId: "all",
    categories: fallbackCategories,
    products: fallbackProducts,
    discount: 0, // in cents
    paymentMethod: "cash",
    cashAmount: 0, // in cents
  };
  let lastReceipt = null;

  const salesLocked = isReadonly;
  let readonlyNotified = false;
  const guardSales = () => {
    if (!salesLocked) return false;
    if (!readonlyNotified) {
      alert("Read-only role: sales actions are disabled.");
      readonlyNotified = true;
    }
    return true;
  };

  const categoryColors = {
    all: "#d0d5dd",
    1: "#1f6feb",      // Beverages
    2: "#f97316",      // Bakery
    3: "#16a34a",      // Snacks
    4: "#8b5cf6",      // Essentials
  };

  const catColor = (id) => categoryColors[String(id)] || "#d7e5ff";

  const qtyBadge = (p) => {
    const qty = Number.parseInt(p.qty_on_hand ?? 0, 10);
    if (!Number.isFinite(qty) || qty <= 0) {
      return { label: "Out of stock", className: "qty-none" };
    }
    if (qty <= 5) {
      return { label: `${qty} left`, className: "qty-low" };
    }
    return { label: `${qty} available`, className: "qty-high" };
  };

  const els = {
    productGrid: document.querySelector("[data-products]"),
    query: document.querySelector("[data-search]"),
    categories: document.querySelector("[data-categories]"),
    cartCount: document.querySelector("[data-cart-count]"),
    cartTotal: document.querySelector("[data-cart-total]"),
    cartTotalDrawer: document.querySelector("[data-cart-total-drawer]"),
    cartList: document.querySelector("[data-cart-list]"),
    cartOverlay: document.querySelector("[data-cart-overlay]"),
    cartViewBtn: document.querySelector("[data-view-cart]"),
    cartCloseBtn: document.querySelector("[data-cart-close]"),
    cartSubtotal: document.querySelector("[data-cart-subtotal]"),
    cartDiscount: document.querySelector("[data-cart-discount]"),
    discountInput: document.querySelector("[data-discount-input]"),
    discountInc: document.querySelector("[data-discount-inc]"),
    discountDec: document.querySelector("[data-discount-dec]"),
    menuOpen: document.querySelector("[data-menu-open]"),
    menuShell: document.querySelector("[data-menu-shell]"),
    menuDrawer: document.querySelector("[data-menu-drawer]"),
    menuOverlay: document.querySelector("[data-menu-overlay]"),
    menuClose: document.querySelector("[data-menu-close]"),
    menuItems: document.querySelectorAll("[data-menu-item]"),
    logoutLink: document.querySelector("[data-logout-link]"),
    logoutDialog: document.querySelector("[data-logout-dialog]"),
    logoutCancel: document.querySelector("[data-logout-cancel]"),
    logoutConfirm: document.querySelector("[data-logout-confirm]"),
    posScreen: document.querySelector("[data-pos-screen]"),
    checkoutScreen: document.querySelector("[data-checkout-screen]"),
    startCheckout: document.querySelector("[data-start-checkout]"),
    checkoutBack: document.querySelector("[data-checkout-back]"),
    checkoutTotal: document.querySelector("[data-checkout-total]"),
    checkoutSubtotal: document.querySelector("[data-checkout-subtotal]"),
    checkoutDiscount: document.querySelector("[data-checkout-discount]"),
    checkoutDue: document.querySelector("[data-checkout-due]"),
    payButtons: document.querySelectorAll("[data-pay-method]"),
    confirmPayment: document.querySelector("[data-confirm-payment]"),
    cashOverlay: document.querySelector("[data-cash-overlay]"),
    cashClose: document.querySelector("[data-cash-close]"),
    cashButtons: document.querySelector("[data-cash-buttons]"),
    cashChange: document.querySelector("[data-cash-change]"),
    completeSale: document.querySelector("[data-complete-sale]"),
    cashAmountDisplay: document.querySelector("[data-cash-amount-display]"),
    cashInputRow: document.querySelector("[data-cash-input-row]"),
    cashInput: document.querySelector("[data-cash-input]"),
    cashDue: document.querySelector("[data-cash-due]"),
    cashChangeLabel: document.querySelector("[data-cash-change-label]"),
    cashLine: document.querySelector("[data-cash-line]"),
    saleComplete: document.querySelector("[data-sale-complete-screen]"),
    completePaid: document.querySelector("[data-complete-paid]"),
    completeChange: document.querySelector("[data-complete-change]"),
    completeChangeDetail: document.querySelector("[data-complete-change-detail]"),
    completeDiscount: document.querySelector("[data-complete-discount]"),
    completeItems: document.querySelector("[data-complete-items]"),
    completeMethod: document.querySelector("[data-complete-method]"),
    completeTxn: document.querySelector("[data-complete-txn]"),
    completeSaleTotal: document.querySelector("[data-complete-sale-total]"),
    completeAmountReceived: document.querySelector("[data-complete-amount-received]"),
    completeChangeDue: document.querySelector("[data-complete-change-due]"),
    completeSubtotal: document.querySelector("[data-complete-subtotal]"),
    completeMethodIcon: document.querySelector("[data-complete-method-icon]"),
    completeTxnCopy: document.querySelector("[data-copy-txn]"),
    completeTs: document.querySelector("[data-complete-ts]"),
    completeCartOverlay: document.querySelector("[data-complete-cart-overlay]"),
    completeCartList: document.querySelector("[data-complete-cart-list]"),
    completeCartTotal: document.querySelector("[data-complete-cart-total]"),
    completeCartClose: document.querySelector("[data-complete-cart-close]"),
    completeCartCount: document.querySelector("[data-complete-cart-count]"),
    completeCartSubtotal: document.querySelector("[data-complete-cart-subtotal]"),
    completeCartDiscount: document.querySelector("[data-complete-cart-discount]"),
    completeCartPaid: document.querySelector("[data-complete-cart-paid]"),
    completeCartChange: document.querySelector("[data-complete-cart-change]"),
    completeCartMethod: document.querySelector("[data-complete-cart-method]"),
    newSale: document.querySelector("[data-new-sale]"),
    printReceipt: document.querySelector("[data-print-receipt]"),
    sendReceipt: document.querySelector("[data-send-receipt]"),
    tenantSelect: document.querySelector("[data-tenant-select]"),
    completeReceipt: document.querySelector("[data-print-receipt]"),
  };
  const reportsRoot = document.querySelector("[data-reports]");

  const applyRoleGuards = () => {
    const toggle = (el) => {
      if (!el) return;
      el.disabled = salesLocked;
      el.setAttribute("aria-disabled", salesLocked ? "true" : "false");
      el.classList.toggle("is-disabled", salesLocked);
    };
    toggle(els.startCheckout);
    toggle(els.confirmPayment);
    toggle(els.completeSale);
    toggle(els.cartViewBtn);
    toggle(els.discountInc);
    toggle(els.discountDec);
    toggle(els.payButtons && els.payButtons[0]);
    if (els.discountInput) {
      els.discountInput.disabled = salesLocked;
      els.discountInput.setAttribute("aria-disabled", salesLocked ? "true" : "false");
    }
    if (els.payButtons) {
      els.payButtons.forEach((btn) => toggle(btn));
    }
  };

  const getQty = (id) => state.cart.get(id) ?? 0;
  const setQty = (id, qty) => {
    if (qty <= 0) state.cart.delete(id);
    else state.cart.set(id, qty);
  };
  const findProduct = (id) => state.products.find((p) => Number(p.id) === Number(id));
  const applySaleStock = (cartItems) => {
    if (!Array.isArray(cartItems) || !cartItems.length) return;
    const nextProducts = state.products.map((p) => {
      const item = cartItems.find((i) => Number(i.id ?? i.product_id) === Number(p.id));
      if (!item) return p;
      const sold = Number.parseInt(item.qty ?? item.quantity ?? 0, 10);
      const current = Number.parseInt(p.qty_on_hand ?? 0, 10);
      const nextQty = Math.max(0, current - (Number.isFinite(sold) ? sold : 0));
      return { ...p, qty_on_hand: nextQty };
    });
    state.products = nextProducts;
  };
  const productStock = (product) => Number.parseInt(product?.qty_on_hand ?? 0, 10);
  const handleStockGuard = (product, nextQty) => {
    if (!product) return { allowed: false, remaining: 0 };
    const stock = productStock(product);
    const remaining = stock - nextQty;
    if (!Number.isFinite(stock) || stock <= 0) {
      showToast(`${product.name ? `"${product.name}"` : "This item"} is out of stock and cannot be added.`, "error");
      return { allowed: false, remaining };
    }
    if (remaining < 0) {
      showToast(`Only ${stock} left for ${product.name ? `"${product.name}"` : "this item"}.`, "error");
      return { allowed: false, remaining };
    }
    if (remaining === 0) {
      showToast(`${product.name ? `"${product.name}"` : "This item"} has no remaining stock after this add.`, "info");
    }
    return { allowed: true, remaining };
  };
  const getCartItems = () => {
    return state.products
      .map((p) => ({
        ...p,
        qty: getQty(p.id),
      }))
      .filter((p) => p.qty > 0);
  };

  const filtered = () => {
    const q = state.query.trim().toLowerCase();
    const cat = state.categoryId;
    return state.products.filter((p) => {
      const matchCat = cat === "all" ? true : String(p.category_id ?? "") === String(cat);
      if (!matchCat) return false;
      if (!q) return true;
      return p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q);
    });
  };

  const totals = () => {
    let count = 0;
    let subtotal = 0;
    for (const p of state.products) {
      const qty = getQty(p.id);
      if (!qty) continue;
      count += qty;
      subtotal += qty * p.price_cents;
    }
    const discount = Math.max(0, Math.min(subtotal, state.discount));
    const total = Math.max(0, subtotal - discount);
    return { count, subtotal, discount, total };
  };

  const renderProducts = () => {
    if (!els.productGrid) return;
    const list = filtered();
    if (!list.length) {
      els.productGrid.innerHTML = `<div class="pill">No products found</div>`;
      return;
    }
    els.productGrid.innerHTML = list
      .map((p) => {
        const qty = getQty(p.id);
        const badge = qtyBadge(p);
        return `
        <div class="tile">
          <div class="title-row">
            <div class="title-left">
              <span class="dot" style="background:${catColor(p.category_id)}"></span>
              <div class="name">${escapeHtml(p.name)}</div>
            </div>
          </div>
          <span class="qty-pill ${badge.className}">${escapeHtml(badge.label)}</span>
          <div class="meta">
            <span class="sku">${escapeHtml(p.sku)}</span>
            <span class="price">${money(p.price_cents)}</span>
          </div>
          <button class="btn btn-primary btn-add" data-add="${p.id}">
            Add
            ${qty ? `<span class="btn-badge">${qty}</span>` : ""}
          </button>
        </div>`;
      })
      .join("");
    if (salesLocked) {
      els.productGrid.querySelectorAll("[data-add]").forEach((btn) => {
        btn.classList.add("is-disabled");
        btn.setAttribute("aria-disabled", "true");
        btn.disabled = true;
      });
    }
  };

  const renderCategories = () => {
    if (!els.categories) return;
    const chips = state.categories.map((c) => {
      const active = String(state.categoryId) === String(c.id);
      return `<button class="cat-chip${active ? " active" : ""}" data-category="${escapeAttr(c.id)}">${escapeHtml(c.name)}</button>`;
    });
    els.categories.innerHTML = chips.join("");
  };

  const renderAll = () => {
    renderCategories();
    renderProducts();
    renderCartSummary();
    renderCartDrawer();
    renderCheckout();
    renderCash();
  };

  const renderCartSummary = () => {
    const t = totals();
    if (els.cartCount) els.cartCount.textContent = `${t.count} item${t.count === 1 ? "" : "s"}`;
    if (els.cartTotal) els.cartTotal.textContent = money(t.total);
    if (els.cartTotalDrawer) els.cartTotalDrawer.textContent = money(t.total);
    if (els.cartSubtotal) els.cartSubtotal.textContent = money(t.subtotal);
    if (els.cartDiscount) els.cartDiscount.textContent = `- ${money(t.discount)}`;
    if (els.discountInput && document.activeElement !== els.discountInput) {
      els.discountInput.value = (t.discount / 100).toFixed(2);
    }
  };

  const renderCartDrawer = () => {
    if (!els.cartList) return;
    const lines = [];
    for (const p of state.products) {
      const qty = getQty(p.id);
      if (!qty) continue;
      lines.push(`
        <div class="cart-row">
          <div class="info">
            <div class="name">${escapeHtml(p.name)}</div>
            <div class="price">${money(p.price_cents)}</div>
          </div>
          <div class="qty">
            <button class="btn btn-ghost" data-dec="${p.id}">-</button>
            <div class="num">${qty}</div>
            <button class="btn btn-primary" data-inc="${p.id}">+</button>
          </div>
        </div>
      `);
    }
    els.cartList.innerHTML = lines.length ? lines.join("") : `<div class="pill">Cart is empty</div>`;
    if (salesLocked) {
      els.cartList.querySelectorAll("[data-inc],[data-dec]").forEach((btn) => {
        btn.classList.add("is-disabled");
        btn.setAttribute("aria-disabled", "true");
        btn.disabled = true;
      });
    }
  };

  const renderCheckout = () => {
    const t = totals();
    if (els.checkoutTotal) els.checkoutTotal.textContent = money(t.total);
    if (els.checkoutSubtotal) els.checkoutSubtotal.textContent = money(t.subtotal);
    if (els.checkoutDiscount) els.checkoutDiscount.textContent = money(t.discount);
    if (els.checkoutDue) els.checkoutDue.textContent = money(t.total);
    updatePaymentButtons();
  };

  const setCashAmount = (cents) => {
    state.cashAmount = Math.max(0, Math.round(cents));
    renderCash();
  };

  const renderCash = () => {
    const t = totals();
    const change = state.cashAmount - t.total;
    const changeLabel = els.cashChangeLabel;
    const changeLine = els.cashLine;
    if (changeLine) {
      changeLine.classList.remove("warning", "success");
    }
    if (changeLabel) {
      if (change < 0) {
        changeLabel.textContent = "Remaining:";
        changeLine?.classList.add("warning");
      } else {
        changeLabel.textContent = "Change:";
        changeLine?.classList.add("success");
      }
    }
    if (els.cashChange) els.cashChange.textContent = money(Math.abs(change));
    if (els.cashAmountDisplay) els.cashAmountDisplay.textContent = money(state.cashAmount);
    if (els.cashDue) els.cashDue.textContent = money(t.total);
    if (els.cashInput && els.cashInputRow && !els.cashInputRow.classList.contains("is-hidden") && document.activeElement !== els.cashInput) {
      els.cashInput.value = (state.cashAmount / 100).toFixed(2);
    }
    if (els.completeSale) {
      const enabled = state.cashAmount >= t.total && t.total > 0;
      els.completeSale.disabled = !enabled;
      els.completeSale.setAttribute("aria-disabled", enabled ? "false" : "true");
    }
    if (els.cashButtons) {
      els.cashButtons.querySelectorAll("[data-cash-amount]").forEach((btn) => {
        const val = btn.getAttribute("data-cash-amount");
        if (!val || val === "enter") return;
        const cents = Number.parseInt(val, 10);
        const strong = Number.isFinite(cents) && t.total > 0 && cents >= t.total;
        btn.classList.toggle("cash-strong", strong);
      });
    }
  };

  const updatePaymentButtons = () => {
    if (!els.payButtons) return;
    els.payButtons.forEach((btn) => {
      const method = btn.getAttribute("data-pay-method");
      const active = method === state.paymentMethod;
      btn.classList.toggle("active", active);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
    });
  };

  const escapeHtml = (s) =>
    String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const escapeAttr = (s) => escapeHtml(s);
  const escapeText = (s) => escapeHtml(s);

  const normalizeSale = (sale) => {
    if (!sale) return null;
    const itemsRaw = Array.isArray(sale.items) ? sale.items : [];
    const items = itemsRaw
      .map((p) => {
        const qty = Number.parseInt(p.qty ?? p.quantity ?? 0, 10);
        const price = Number.parseInt(p.unit_price_cents ?? p.price_cents ?? 0, 10);
        const line = Number.parseInt(p.line_total_cents ?? p.line_total ?? qty * price, 10);
        return {
          name: String(p.name ?? ""),
          qty: Number.isFinite(qty) ? qty : 0,
          price_cents: Number.isFinite(price) ? price : 0,
          line_total: Number.isFinite(line) ? line : 0,
        };
      })
      .filter((p) => p.qty > 0);
    const subtotal = Number.parseInt(sale.subtotal_cents ?? sale.subtotal ?? 0, 10);
    const discount = Number.parseInt(sale.discount_cents ?? sale.discount ?? 0, 10);
    const total = Number.parseInt(sale.total_cents ?? sale.total ?? 0, 10);
    const paid = Number.parseInt(sale.amount_received_cents ?? sale.paid ?? 0, 10);
    const change = Number.parseInt(sale.change_cents ?? sale.change ?? Math.max(0, paid - total), 10);
    const tsRaw = sale.paid_at || sale.created_at || sale.timestamp || null;
    const ts = tsRaw ? new Date(tsRaw) : new Date();
    const txId = String(sale.transaction_id ?? sale.transactionId ?? sale.receipt_no ?? "");
    const receiptNo = String(sale.receipt_no ?? sale.receiptNo ?? txId);
    return {
      receiptNo,
      receiptNoShort: receiptNo && receiptNo.length > 14 ? `${receiptNo.slice(0, 6)}...${receiptNo.slice(-4)}` : receiptNo,
      transactionId: txId || receiptNo,
      timestamp: ts.toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
      }),
      rawTimestamp: tsRaw,
      subtotal: Number.isFinite(subtotal) ? subtotal : 0,
      discount: Number.isFinite(discount) ? discount : 0,
      total: Number.isFinite(total) ? total : 0,
      paid: Number.isFinite(paid) ? paid : 0,
      change: Number.isFinite(change) ? Math.max(0, change) : 0,
      cashier: sale.cashier_name || sale.cashier_username || cashierName,
      cashier_username: sale.cashier_username || null,
      items,
      paymentMethod: String(sale.payment_method ?? sale.paymentMethod ?? "cash").toLowerCase(),
      tenantName: sale.tenant_name || tenantName,
      tenantAddress: sale.tenant_address || tenantAddress,
      tenantContact: sale.tenant_contact || tenantContact,
    };
  };

  const buildReceiptData = (saleOverride = null) => {
    const normalized = normalizeSale(saleOverride || lastReceipt);
    if (normalized && normalized.items && normalized.items.length && normalized.total > 0) {
      return normalized;
    }
    return null;
  };

  const buildReceiptText = (saleOverride = null) => {
    const data = buildReceiptData(saleOverride);
    if (!data) return null;
    const money2 = (cents) =>
      (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const lineSep = "-".repeat(38);
    const itemsBlock = data.items
      .map((p) => {
        const qtyLine = `${p.qty} x ${money2(p.price_cents)}`;
        const totalLine = money2(p.line_total);
        const spacing = Math.max(2, 34 - qtyLine.length - totalLine.length);
        return `${p.name}\n${qtyLine}${" ".repeat(spacing)}${totalLine}`;
      })
      .join("\n");

    const lines = [
      "Plughub POS Receipt",
      data.tenantName ? data.tenantName : "",
      data.tenantAddress ? data.tenantAddress : "",
      data.tenantContact ? data.tenantContact : "",
      "",
      `Receipt No: ${data.receiptNo}`,
      `Transaction: ${data.transactionId}`,
      `Date: ${data.timestamp}`,
      `Cashier: ${data.cashier}`,
      lineSep,
      itemsBlock,
      lineSep,
      `Subtotal: ${money2(data.subtotal)}`,
      `Discount: ${money2(data.discount)}`,
      `Total: ${money2(data.total)}`,
      `Received: ${money2(data.paid)}`,
      `Change: ${money2(data.change)}`,
      `Payment Method: ${data.paymentMethod ? data.paymentMethod.toUpperCase() : "CASH"}`,
      "",
      "Thank you for your purchase!",
    ];
    return lines.filter(Boolean).join("\n");
  };

  const openPrintReceipt = (saleOverride = null) => {
    const data = buildReceiptData(saleOverride);
    if (!data) {
      alert("No sale available to print.");
      return false;
    }
    const money2 = (cents) => (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const lineSep = "-".repeat(32);
    const itemsBlock = data.items
      .map((p) => {
        const qtyLine = `${p.qty} x ${money2(p.price_cents)}`;
        const totalLine = money2(p.line_total);
        return [
          escapeText(p.name),
          `${qtyLine}${" ".repeat(Math.max(2, 28 - qtyLine.length - totalLine.length))}${totalLine}`,
          ""
        ].join("\n");
      })
      .join("\n");
    const doc = `
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Receipt</title>
    <style>
      @page { margin: 10mm; }
      body { font-family: "Courier New", monospace; font-size: 13px; color: #000; margin: 0; padding: 10px; }
      pre { white-space: pre-wrap; word-break: break-word; }
      .no-print { display: none; }
      @media print {
        .print-btn, .no-print { display: none; }
      }
    </style>
  </head>
  <body>
    <pre>
             ${escapeText(data.tenantName || "Plughub POS")}
                OFFICIAL SALES RECEIPT
${data.tenantAddress ? `\n${escapeText(data.tenantAddress)}` : ""}${data.tenantContact ? `\n${escapeText(data.tenantContact)}` : ""}
\nReceipt No : ${escapeText(data.receiptNo)}
Date       : ${escapeText(data.timestamp)}
Cashier    : ${escapeText(data.cashier)}
${lineSep}
${itemsBlock}
${lineSep}
Subtotal:                   ${money2(data.subtotal)}
Discount:                   ${money2(data.discount)}
TOTAL:                      ${money2(data.total)}
${lineSep}
Payment Method: ${data.paymentMethod ? data.paymentMethod.toUpperCase() : "CASH"}
Amount Received:            ${money2(data.paid)}
Change Given:               ${money2(Math.max(0, data.change))}
\nThank you for your purchase!
This receipt serves as proof of payment.
Powered by PlugHub POS
    </pre>
  </body>
</html>
    `;
    const win = window.open("", "_blank", "width=420,height=720");
    if (!win) {
      alert("Please allow popups to print.");
      return false;
    }
    win.document.write(doc);
    win.document.close();
    win.focus();
    win.print();
    return true;
  };

  const loadData = async () => {
    try {
      const [cRes, pRes] = await Promise.all([
        fetch("/api/categories"),
        fetch("/api/products"),
      ]);

      if (cRes.ok) {
        const data = await cRes.json();
        if (data?.ok && Array.isArray(data.categories)) {
          const cats = [{ id: "all", name: "All" }, ...data.categories];
          state.categories = cats;
        }
      }

      if (pRes.ok) {
        const data = await pRes.json();
        if (data?.ok && Array.isArray(data.products)) {
          state.products = data.products;
        }
      }
    } catch (e) {
      console.warn("Using fallback data", e);
    } finally {
      renderAll();
    }
  };

  document.addEventListener("click", (e) => {
    const t = e.target;
    if (!(t instanceof HTMLElement)) return;
    const addId = t.getAttribute("data-add");
    const catId = t.getAttribute("data-category");
    const incId = t.getAttribute("data-inc");
    const decId = t.getAttribute("data-dec");

    if (salesLocked && (addId || incId || decId)) {
      guardSales();
      return;
    }

    if (addId) {
      const id = Number(addId);
      const product = findProduct(id);
      const nextQty = getQty(id) + 1;
      const guard = handleStockGuard(product, nextQty);
      if (!guard.allowed) return;
      setQty(id, nextQty);
      renderAll();
    } else if (incId) {
      const id = Number(incId);
      const product = findProduct(id);
      const nextQty = getQty(id) + 1;
      const guard = handleStockGuard(product, nextQty);
      if (!guard.allowed) return;
      setQty(id, nextQty);
      renderAll();
    } else if (decId) {
      const id = Number(decId);
      setQty(id, getQty(id) - 1);
      renderAll();
    } else if (catId !== null && catId !== undefined) {
      state.categoryId = catId;
      renderAll();
    }
  });

  els.query?.addEventListener("input", (e) => {
    state.query = e.target.value ?? "";
    renderProducts();
  });

  const openCart = () => {
    if (!els.cartOverlay) return;
    els.cartOverlay.classList.add("active");
  };
  const closeCart = () => {
    if (!els.cartOverlay) return;
    els.cartOverlay.classList.remove("active");
  };
  const showCheckout = () => {
    closeCart();
    if (els.posScreen) els.posScreen.classList.add("is-hidden");
    if (els.checkoutScreen) els.checkoutScreen.classList.remove("is-hidden");
    renderCheckout();
    window.scrollTo({ top: 0, behavior: "smooth" });
  };
  const exitCheckout = () => {
    if (els.checkoutScreen) els.checkoutScreen.classList.add("is-hidden");
    if (els.posScreen) els.posScreen.classList.remove("is-hidden");
  };
  els.cartViewBtn?.addEventListener("click", () => {
    renderCartDrawer();
    openCart();
  });
  els.cartCloseBtn?.addEventListener("click", closeCart);
  els.cartOverlay?.addEventListener("click", (e) => {
    if (e.target === els.cartOverlay) closeCart();
  });
  els.startCheckout?.addEventListener("click", () => {
    if (guardSales()) return;
    showCheckout();
  });
  els.checkoutBack?.addEventListener("click", exitCheckout);
  const showCashInputRow = (show) => {
    if (!els.cashInputRow) return;
    els.cashInputRow.classList.toggle("is-hidden", !show);
    if (show && els.cashInput) {
      els.cashInput.value = (state.cashAmount / 100).toFixed(2);
      els.cashInput.focus();
      els.cashInput.select();
    }
  };
  const showCashSheet = () => {
    setCashAmount(0);
    showCashInputRow(false);
    if (els.cashOverlay) els.cashOverlay.classList.remove("is-hidden");
    renderCash();
  };
  const closeCashSheet = () => {
    if (els.cashOverlay) els.cashOverlay.classList.add("is-hidden");
  };
  els.confirmPayment?.addEventListener("click", () => {
    if (guardSales()) return;
    if (state.paymentMethod === "cash") {
      showCashSheet();
    } else {
      // placeholder for QR flow
      alert("QR Code payment coming soon.");
    }
  });
  els.cashClose?.addEventListener("click", closeCashSheet);
  els.cashOverlay?.addEventListener("click", (e) => {
    if (e.target === els.cashOverlay) closeCashSheet();
  });

  const lockBodyScroll = (lock) => {
    if (lock) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
  };
  const openMenu = () => {
    if (!els.menuShell || !els.menuDrawer) return;
    els.menuShell.classList.add("is-open");
    els.menuDrawer.setAttribute("aria-hidden", "false");
    lockBodyScroll(true);
  };
  const closeMenu = () => {
    if (!els.menuShell || !els.menuDrawer) return;
    els.menuShell.classList.remove("is-open");
    els.menuDrawer.setAttribute("aria-hidden", "true");
    lockBodyScroll(false);
  };
  const setActiveMenu = () => {
    if (!els.menuItems) return;
    const path = (window.location && window.location.pathname) || "";
    els.menuItems.forEach((item) => {
      const route = item.getAttribute("data-route");
      if (route && path.startsWith(route)) {
        item.classList.add("is-active");
      } else {
        item.classList.remove("is-active");
      }
    });
  };
  setActiveMenu();
  els.menuItems?.forEach((item) => {
    item.addEventListener("click", () => closeMenu());
  });
  els.menuOpen?.addEventListener("click", (e) => {
    e.preventDefault();
    const open = els.menuShell?.classList.contains("is-open");
    if (open) closeMenu();
    else openMenu();
  });
  els.menuClose?.addEventListener("click", (e) => {
    e.preventDefault();
    closeMenu();
  });
  els.menuOverlay?.addEventListener("click", closeMenu);
  let menuTouchStartX = null;
  els.menuDrawer?.addEventListener(
    "touchstart",
    (e) => {
      if (!e.touches || !e.touches[0]) return;
      menuTouchStartX = e.touches[0].clientX;
    },
    { passive: true },
  );
  els.menuDrawer?.addEventListener(
    "touchend",
    (e) => {
      if (menuTouchStartX === null) return;
      const endX = e.changedTouches && e.changedTouches[0] ? e.changedTouches[0].clientX : menuTouchStartX;
      if (endX - menuTouchStartX <= -60) closeMenu();
      menuTouchStartX = null;
    },
    { passive: true },
  );
  els.menuDrawer?.addEventListener(
    "touchcancel",
    () => {
      menuTouchStartX = null;
    },
    { passive: true },
  );

  const openLogoutDialog = () => {
    if (!els.logoutDialog) return false;
    els.logoutDialog.classList.add("is-open");
    els.logoutDialog.setAttribute("aria-hidden", "false");
    lockBodyScroll(true);
    return true;
  };
  const closeLogoutDialog = () => {
    if (!els.logoutDialog) return;
    els.logoutDialog.classList.remove("is-open");
    els.logoutDialog.setAttribute("aria-hidden", "true");
    lockBodyScroll(false);
  };
  els.logoutLink?.addEventListener("click", (e) => {
    if (!els.logoutDialog) return;
    e.preventDefault();
    closeMenu();
    openLogoutDialog();
  });
  els.logoutCancel?.addEventListener("click", (e) => {
    e.preventDefault();
    closeLogoutDialog();
  });
  els.logoutConfirm?.addEventListener("click", () => {
    closeLogoutDialog();
  });
  els.logoutDialog?.addEventListener("click", (e) => {
    if (e.target === els.logoutDialog) closeLogoutDialog();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (els.logoutDialog?.classList.contains("is-open")) {
        closeLogoutDialog();
        return;
      }
      closeMenu();
    }
  });

  const adjustDiscount = (delta) => {
    const t = totals();
    state.discount = Math.max(0, Math.min(t.subtotal, state.discount + delta));
    renderAll();
  };

  const makeHoldControl = (el, delta) => {
    if (!el) return;
    let holdTimeout = null;
    let holdInterval = null;
    const stop = () => {
      if (holdTimeout) {
        clearTimeout(holdTimeout);
        holdTimeout = null;
      }
      if (holdInterval) {
        clearInterval(holdInterval);
        holdInterval = null;
      }
    };
    const start = (e) => {
      if (salesLocked) {
        guardSales();
        return;
      }
      e.preventDefault();
      adjustDiscount(delta); // immediate tick
      stop();
      holdTimeout = setTimeout(() => {
        holdInterval = setInterval(() => adjustDiscount(delta), 80);
      }, 350);
      const endEvents = ["mouseup", "touchend", "touchcancel", "mouseleave"];
      endEvents.forEach((ev) => {
        document.addEventListener(ev, stop, { once: true });
      });
    };
    el.addEventListener("mousedown", start);
    el.addEventListener("touchstart", start, { passive: false });
  };
  makeHoldControl(els.discountInc, 100);  // +₱1.00
  makeHoldControl(els.discountDec, -100); // -₱1.00 (clamped at 0)

  els.discountInput?.addEventListener("input", (e) => {
    if (salesLocked) {
      guardSales();
      e.target.value = "0.00";
      return;
    }
    const val = parseFloat(e.target.value);
    const cents = Number.isFinite(val) ? Math.round(val * 100) : 0;
    const t = totals();
    state.discount = Math.max(0, Math.min(t.subtotal, cents));
    renderAll();
  });

  if (els.payButtons) {
    els.payButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        if (guardSales()) return;
        const method = btn.getAttribute("data-pay-method");
        if (!method) return;
        state.paymentMethod = method;
        renderCheckout();
      });
    });
  }

  els.cashButtons?.addEventListener("click", (e) => {
    const btn = e.target instanceof HTMLElement ? e.target.closest("[data-cash-amount]") : null;
    if (!btn) return;
    if (guardSales()) return;
    const val = btn.getAttribute("data-cash-amount");
    if (!val) return;
    if (val === "enter") {
      showCashInputRow(true);
      // Do not prefill; wait for user input.
      return;
    }
    showCashInputRow(false);
    const cents = Number.parseInt(val, 10);
    if (Number.isFinite(cents)) setCashAmount(cents);
  });

  const methodLabel = (method) => {
    const m = (method || "").toLowerCase();
    if (m === "qr") return "QR Code";
    if (m === "card") return "Card";
    if (m === "transfer") return "Bank Transfer";
    if (m === "mobile_money") return "Mobile Money";
    return "Cash";
  };

  const methodIcon = (method) => {
    const m = (method || "").toLowerCase();
    if (m === "qr") return "[QR]";
    if (m === "card") return "[CARD]";
    if (m === "transfer" || m === "mobile_money") return "[TRF]";
    return "[CASH]";
  };
  const renderSaleComplete = (sale) => {
    const data = normalizeSale(sale);
    if (!data) {
      alert("Could not display sale summary.");
      return;
    }
    lastReceipt = data;
    const change = data.change;
    const paid = data.paid;
    const total = data.total;
    const method = methodLabel(data.paymentMethod);
    const itemCount = data.items.reduce((sum, p) => sum + p.qty, 0);
    const shortTxn = data.transactionId && data.transactionId.length > 14
      ? `${data.transactionId.slice(0, 6)}...${data.transactionId.slice(-4)}`
      : data.transactionId || "N/A";

    if (els.completePaid) els.completePaid.textContent = money(paid);
    if (els.completeChange) els.completeChange.textContent = `Change Due ${money(change)}`;
    if (els.completeChangeDetail) els.completeChangeDetail.textContent = money(change);
    if (els.completeDiscount) els.completeDiscount.textContent = money(data.discount);
    if (els.completeItems) els.completeItems.textContent = `${itemCount}`;
    if (els.completeMethod) els.completeMethod.textContent = method;
    if (els.completeMethodIcon) els.completeMethodIcon.textContent = methodIcon(data.paymentMethod);
    if (els.completeTxn) {
      els.completeTxn.textContent = shortTxn;
      els.completeTxn.dataset.fullTxn = data.transactionId || shortTxn;
    }
    if (els.completeSaleTotal) els.completeSaleTotal.textContent = money(total);
    if (els.completeAmountReceived) els.completeAmountReceived.textContent = money(paid);
    if (els.completeChangeDue) els.completeChangeDue.textContent = money(change);
    if (els.completeSubtotal) els.completeSubtotal.textContent = money(data.subtotal);
    if (els.completeTs) els.completeTs.textContent = data.timestamp;
    if (els.completeCartList && els.completeCartTotal && els.completeCartCount) {
      const rows = data.items.map((p) => {
        const line = p.line_total || p.qty * p.price_cents;
        return `
          <div class="complete-cart-row">
            <div class="complete-cart-info">
              <div class="complete-cart-name">${escapeHtml(p.name)}</div>
              <div class="complete-cart-meta">
                <span>Qty: ${p.qty}</span>
                <span>${money(p.price_cents)} each</span>
              </div>
            </div>
            <div class="complete-cart-total">${money(line)}</div>
          </div>
        `;
      });
      els.completeCartList.innerHTML = rows.join("") || `<div class="pill">No items</div>`;
      const cartSubtotal = data.items.reduce((sum, p) => sum + p.qty * p.price_cents, 0);
      const cartDiscount = data.discount ?? Math.max(0, cartSubtotal - total);
      els.completeCartTotal.textContent = money(total);
      if (els.completeCartSubtotal) els.completeCartSubtotal.textContent = money(cartSubtotal);
      if (els.completeCartDiscount) els.completeCartDiscount.textContent = money(cartDiscount);
      if (els.completeCartPaid) els.completeCartPaid.textContent = money(paid);
      if (els.completeCartChange) els.completeCartChange.textContent = money(change);
      if (els.completeCartMethod) els.completeCartMethod.textContent = method;
      els.completeCartCount.textContent = `${itemCount} item${itemCount === 1 ? "" : "s"}`;
    }
    closeCashSheet();
    if (els.checkoutScreen) els.checkoutScreen.classList.add("is-hidden");
    if (els.posScreen) els.posScreen.classList.add("is-hidden");
    if (els.saleComplete) els.saleComplete.classList.remove("is-hidden");
  };

  const recordSale = async () => {
    if (guardSales()) return;
    const t = totals();
    if (t.total <= 0) {
      alert("Cart is empty.");
      return;
    }
    if (state.cashAmount < t.total) {
      alert("Amount received must cover the total due.");
      return;
    }
    const cartItems = getCartItems();
    const items = cartItems.map((p) => ({
      product_id: p.id,
      name: p.name,
      sku: p.sku || "",
      price_cents: p.price_cents,
      unit_price_cents: p.price_cents,
      qty: p.qty,
      line_total_cents: p.qty * p.price_cents,
    }));
    const payload = {
      items,
      subtotal_cents: t.subtotal,
      discount_cents: t.discount,
      total_cents: t.total,
      amount_received_cents: state.cashAmount,
      change_cents: Math.max(0, state.cashAmount - t.total),
      payment_method: state.paymentMethod || "cash",
    };
    const btn = els.completeSale;
    const prevText = btn?.textContent;
    if (btn) {
      btn.disabled = true;
      btn.textContent = "Saving...";
    }
    try {
      const res = await fetch("/api/sales", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data?.ok || !data.sale) {
        throw new Error(data?.error || "Could not record sale.");
      }
      applySaleStock(cartItems);
      renderAll();
      renderSaleComplete(data.sale);
    } catch (err) {
      alert(err?.message || "Could not record sale.");
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = prevText;
      }
    }
  };

  els.completeSale?.addEventListener("click", () => {
    recordSale();
  });
  els.cashInput?.addEventListener("input", (e) => {
    if (salesLocked) {
      guardSales();
      e.target.value = "";
      return;
    }
    const val = parseFloat(e.target.value);
    const cents = Number.isFinite(val) ? Math.round(val * 100) : 0;
    setCashAmount(cents);
  });

  els.newSale?.addEventListener("click", () => {
    state.cart.clear();
    state.discount = 0;
    state.cashAmount = 0;
    lastReceipt = null;
    renderAll();
    if (els.saleComplete) els.saleComplete.classList.add("is-hidden");
    if (els.checkoutScreen) els.checkoutScreen.classList.add("is-hidden");
    if (els.posScreen) els.posScreen.classList.remove("is-hidden");
  });

  els.printReceipt?.addEventListener("click", () => {
    openPrintReceipt();
  });
  els.sendReceipt?.addEventListener("click", () => {
    const text = buildReceiptText();
    const data = buildReceiptData();
    if (!text || !data) {
      showToast("No receipt available to send.", "error");
      return;
    }
    // Offer a lightweight text receipt download for attachment
    const filename = `receipt-${(data.receiptNo || data.transactionId || "sale").replace(/[^a-z0-9_-]+/gi, "-")}.txt`;
    try {
      const blob = new Blob([text], { type: "text/plain" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      link.style.display = "none";
      document.body.appendChild(link);
      link.click();
      setTimeout(() => {
        URL.revokeObjectURL(link.href);
        link.remove();
      }, 1200);
    } catch (_) {
      // ignore download errors
    }

    const subject = `Receipt ${data.receiptNo || data.transactionId || ""}`.trim() || "Your receipt";
    const mailto = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(text)}`;
    window.location.href = mailto;
    showToast("Opening your email draft with the receipt text.", "info");
  });
  els.completeTxnCopy?.addEventListener("click", async () => {
    const full = els.completeTxn?.dataset.fullTxn || els.completeTxn?.textContent || "";
    if (!full) return;
    try {
      await navigator.clipboard.writeText(full);
      alert("Transaction ID copied.");
    } catch {
      alert("Copy not available in this browser.");
    }
  });

  const openCompleteCart = () => {
    if (els.completeCartOverlay) els.completeCartOverlay.classList.remove("is-hidden");
  };
  const closeCompleteCart = () => {
    if (els.completeCartOverlay) els.completeCartOverlay.classList.add("is-hidden");
  };
  document.querySelector("[data-view-details]")?.addEventListener("click", (e) => {
    e.preventDefault();
    openCompleteCart();
  });
  els.completeCartClose?.addEventListener("click", closeCompleteCart);
  els.completeCartOverlay?.addEventListener("click", (e) => {
    if (e.target === els.completeCartOverlay) closeCompleteCart();
  });

  els.tenantSelect?.addEventListener("change", () => {
    const form = els.tenantSelect.closest("form");
    if (form) form.submit();
  });

  const salesHistoryRoot = document.querySelector("[data-sales-history]");
  if (salesHistoryRoot) {
    const listEl = salesHistoryRoot.querySelector("[data-sales-list]");
    const queryInput = salesHistoryRoot.querySelector("[data-sales-query]");
    const countEl = salesHistoryRoot.querySelector("[data-sales-count]");
    const searchBtn = salesHistoryRoot.querySelector("[data-sales-search]");
    const refreshBtn = salesHistoryRoot.querySelector("[data-sales-refresh]");
    const lastRefEl = salesHistoryRoot.querySelector("[data-sales-lastref]");
    const menuWrap = salesHistoryRoot.querySelector("[data-sales-menu]");
    const menuToggle = salesHistoryRoot.querySelector("[data-sales-menu-toggle]");
    const menuDropdown = salesHistoryRoot.querySelector("[data-sales-menu-dropdown]");
    const notifyError = (msg) => {
      if (typeof showToast === "function") {
        showToast(msg, "error");
      } else {
        alert(msg);
      }
    };

    const setLoading = (loading) => {
      if (!listEl) return;
      listEl.classList.toggle("is-loading", loading);
    };

    const formatDateTime = (value) => {
      if (!value) return "N/A";
      const dt = new Date(value);
      if (Number.isNaN(dt.getTime())) return "N/A";
      return dt.toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
      });
    };

    const renderSales = (sales) => {
      if (!listEl) return;
      if (!Array.isArray(sales) || sales.length === 0) {
        listEl.innerHTML = `
          <div class="empty-card">
            <div class="empty-icon">🧾</div>
            <div>No sales found</div>
            <div class="microcopy">Try searching by transaction ID, receipt number, or cashier.</div>
          </div>
        `;
        if (countEl) countEl.textContent = "0";
        return;
      }
      const cards = sales.map((s) => {
        const receipt = escapeHtml(s.receipt_no || s.transaction_id || String(s.id || ""));
        const txn = escapeHtml(s.transaction_id || "");
        const cashier = escapeHtml(s.cashier_name || s.cashier_username || "Cashier");
        const method = methodLabel(s.payment_method || "cash");
        const when = formatDateTime(s.paid_at || s.created_at);
        const tenantLabel = s.tenant_name ? `<span class="pill pill-ghost">${escapeHtml(s.tenant_name)}</span>` : "";
        const amountReceived = Number(s.amount_received_cents || 0);
        const change = Number(s.change_cents || 0);
        return `
          <article class="sale-card">
            <div class="sale-head">
              <div>
                <div class="sale-receipt">${receipt}</div>
                <div class="sale-meta">
                  <span class="meta-pill">Txn: ${txn || "—"}</span>
                  <span class="meta-pill">Cashier: ${cashier}</span>
                  ${tenantLabel}
                </div>
              </div>
              <div class="sale-amount">${money(Number(s.total_cents || 0))}</div>
            </div>
            <div class="sale-footer">
              <div class="sale-meta">
                <span>${when}</span>
                <span class="meta-pill">Received: ${money(amountReceived)}</span>
                <span class="meta-pill">Change: ${money(change)}</span>
                <span class="method-pill">${method}</span>
              </div>
              <div class="sale-actions">
                <button class="btn btn-primary btn-compact" type="button" data-sale-ref="${escapeAttr(s.transaction_id || s.receipt_no || String(s.id || ""))}">Reprint</button>
              </div>
            </div>
          </article>
        `;
      });
      listEl.innerHTML = cards.join("");
      if (countEl) countEl.textContent = String(sales.length);
    };

    const loadSales = async (q = "") => {
      setLoading(true);
      try {
        const query = (q || "").trim();
        const url = query ? `/api/sales?q=${encodeURIComponent(query)}` : "/api/sales";
        const res = await fetch(url);
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data?.ok) {
          throw new Error(data?.error || "Could not load sales.");
        }
        renderSales(data.sales || []);
        if (lastRefEl) {
          const now = new Date();
          lastRefEl.textContent = `Updated ${now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}`;
        }
      } catch (err) {
        notifyError(err?.message || "Could not load sales.");
      } finally {
        setLoading(false);
      }
    };

    const handleReprint = async (ref) => {
      if (!ref) return;
      try {
        const res = await fetch(`/api/sales/${encodeURIComponent(ref)}`);
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data?.ok || !data.sale) {
          throw new Error(data?.error || "Sale not found.");
        }
        lastReceipt = normalizeSale(data.sale);
        openPrintReceipt(data.sale);
      } catch (err) {
        alert(err?.message || "Could not load sale.");
      }
    };

    listEl?.addEventListener("click", (e) => {
      const btn = e.target instanceof HTMLElement ? e.target.closest("[data-sale-ref]") : null;
      if (!btn) return;
      const ref = btn.getAttribute("data-sale-ref") || "";
      handleReprint(ref);
    });

    searchBtn?.addEventListener("click", () => {
      const q = queryInput?.value || "";
      loadSales(q);
    });
    refreshBtn?.addEventListener("click", () => {
      if (queryInput) queryInput.value = "";
      loadSales("");
    });
    queryInput?.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        loadSales(queryInput?.value || "");
      }
    });

    loadSales();

    // menu interactions
    const closeMenu = () => {
      menuWrap?.classList.remove("is-open");
    };
    menuToggle?.addEventListener("click", (e) => {
      e.preventDefault();
      menuWrap?.classList.toggle("is-open");
    });
    document.addEventListener("click", (e) => {
      const target = e.target;
      if (!(target instanceof HTMLElement)) return;
      if (menuWrap && menuWrap.contains(target)) return;
      closeMenu();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeMenu();
    });
  }

  if (reportsRoot) {
    const q = (sel) => (reportsRoot ? reportsRoot.querySelector(sel) : null);
    const qAll = (sel) => (reportsRoot ? reportsRoot.querySelectorAll(sel) : []);
    const downloadSheet = document.querySelector("[data-download-sheet]");
    const downloadClose = document.querySelector("[data-download-close]");
    const downloadBtn = q("[data-report-download]");
    const downloadCsv = document.querySelector("[data-download-csv]");
    const downloadXlsx = document.querySelector("[data-download-xlsx]");
    const summaryText = document.querySelector("[data-report-summary-text]");
    const tabButtons = qAll("[data-report-tab]");
    const listEl = q("[data-report-list]");
    const kpiEl = q("[data-report-kpis]");
    const kpiSecondary = q("[data-kpi-secondary]");
    const kpiToggle = q("[data-kpi-toggle]");
    const kpiToggleLabel = q("[data-kpi-toggle-label]");
    const periodEl = q("[data-kpi-period]");
    const paymentsEl = q("[data-kpi-payments]");
    const topNameEl = q("[data-kpi-top-name]");
    const tabLabel = q("[data-report-tab-label]");
    const resultsSection = q("[data-report-results]");
    const groupToggle = q("[data-group-toggle]");
    const groupCheckbox = q("[data-aggregate-report]");
    const paginationEl = q("[data-report-pagination]");
    const paginationCopy = q("[data-report-pagecopy]");
    const prevBtn = q("[data-report-prev]");
    const nextBtn = q("[data-report-next]");
    const skeleton = q("[data-report-skeleton]");
    const dateFromInput = q("[data-report-from]");
    const dateToInput = q("[data-report-to]");
    const filterApplyBtns = document.querySelectorAll("[data-report-apply]");
    const filterClearBtns = document.querySelectorAll("[data-report-clear]");
    const helpBtn = q("[data-report-help]");

    const reportData = {
      sales: [],
      items: [],
    };
    let hasApplied = false;
    let kpiExpanded = false;
    let kpiSnapshot = null;
    const comparisonState = {
      percent: null,
      loading: false,
      previousNet: null,
    };
    const paymentLabels = {
      cash: "Cash",
      qr: "QR Code",
      card: "Card",
      transfer: "Transfer",
      mobile_money: "Mobile Money",
    };

    const reportState = {
      tab: "sales",
      page: 1,
      pageSize: 50,
      sortBy: "date_desc",
      filters: {
        from: "",
        to: "",
        aggregate: true,
      },
      loading: false,
    };

    const parseDate = (value) => {
      if (!value) return null;
      const d = new Date(value);
      return Number.isNaN(d.getTime()) ? null : d;
    };
    const fmtDate = (value) => {
      const d = parseDate(value);
      if (!d) return "";
      return d.toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" });
    };
    const fmtDateShort = (value) => {
      const d = parseDate(value);
      if (!d) return "";
      return d.toLocaleDateString("en-CA");
    };
    const getRangeCopy = () => {
      const { from, to } = reportState.filters;
      if (!from && !to) return "All dates";
      if (from && to) return `${fmtDate(from)}–${fmtDate(to)}`;
      return from ? `From ${fmtDate(from)}` : `Until ${fmtDate(to)}`;
    };

    const formatNumber = (value, fractionDigits = 1) =>
      Number(value ?? 0).toLocaleString(undefined, {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
      });

    const normalizePayment = (value) => {
      const key = (value || "").toLowerCase();
      return paymentLabels[key] ? key : "other";
    };

    const syncSecondaryVisibility = () => {
      if (!kpiSecondary) return;
      const isDesktop = window.matchMedia("(min-width: 1024px)").matches;
      if (isDesktop) {
        kpiSecondary.hidden = false;
        kpiEl?.classList.add("is-expanded");
        if (kpiToggle) kpiToggle.setAttribute("aria-expanded", "true");
        return;
      }
      kpiSecondary.hidden = !kpiExpanded;
      kpiEl?.classList.toggle("is-expanded", kpiExpanded);
      if (kpiToggleLabel) kpiToggleLabel.textContent = kpiExpanded ? "Hide Metrics" : "View More Metrics";
      if (kpiToggle) kpiToggle.setAttribute("aria-expanded", kpiExpanded ? "true" : "false");
    };

    const renderComparison = () => {
      if (!periodEl) return;
      periodEl.classList.remove("trend-up", "trend-down", "trend-flat");
      if (comparisonState.loading) {
        periodEl.textContent = "Comparing…";
        periodEl.classList.add("trend-flat");
        return;
      }
      const pct = comparisonState.percent;
      if (pct === null || !Number.isFinite(pct)) {
        periodEl.textContent = "No previous period";
        periodEl.classList.add("trend-flat");
        return;
      }
      const rounded = Math.abs(pct).toFixed(1);
      if (pct > 0) {
        periodEl.textContent = `↑ ${rounded}% vs previous period`;
        periodEl.classList.add("trend-up");
      } else if (pct < 0) {
        periodEl.textContent = `↓ ${rounded}% vs previous period`;
        periodEl.classList.add("trend-down");
      } else {
        periodEl.textContent = "→ 0% vs previous period";
        periodEl.classList.add("trend-flat");
      }
    };

    const isoDate = (d) => (d instanceof Date ? d.toLocaleDateString("en-CA") : "");

    const computePreviousRange = () => {
      const { from, to } = reportState.filters;
      const start = parseDate(from);
      const end = parseDate(to);
      if (!start || !end) return null;
      const diffMs = Math.max(end.getTime() - start.getTime(), 0);
      const days = Math.max(1, Math.round(diffMs / 86400000) + 1);
      const prevEnd = new Date(start.getTime() - 86400000);
      const prevStart = new Date(prevEnd.getTime() - (days - 1) * 86400000);
      return {
        from: isoDate(prevStart),
        to: isoDate(prevEnd),
      };
    };

    const setLoading = (loading) => {
      reportState.loading = loading;
      if (!listEl) return;
      if (loading) {
        const skeletonHtml =
          (skeleton && skeleton.outerHTML) ||
          '<div class="skeleton-list"><div class="skeleton-card"></div><div class="skeleton-card"></div><div class="skeleton-card"></div></div>';
        listEl.innerHTML = skeletonHtml;
      }
    };

    const activeRows = () => {
      const tab = reportState.tab;
      const f = reportState.filters;
      const fromDate = parseDate(f.from);
      const toDate = parseDate(f.to);
      const search = (f.search || "").toLowerCase();
      const applyDate = (value, allowMissing = false) => {
        if (!value) return allowMissing;
        const d = parseDate(value);
      if (!d) return allowMissing;
      if (fromDate && d < fromDate) return false;
      if (toDate && d > toDate) return false;
      return true;
    };

      const filterSales = reportData.sales.filter((row) => {
        if (fromDate || toDate) {
          if (!applyDate(row.created_at)) return false;
        }
        if (search) {
          const hay = `${row.receipt ?? ""} ${row.id ?? ""} ${row.cashier ?? ""}`.toLowerCase();
          if (!hay.includes(search)) return false;
        }
        return true;
      });

      // Items are already filtered by the API date range; avoid double-filtering on the client
      // to prevent mismatches with browser date parsing quirks.
      const filterItems = reportData.items;

      const sorter = (rows) => {
        const sortKey = reportState.sortBy;
        const sorters = {
          date_desc: (a, b) => parseDate(b.created_at || b.last_sold) - parseDate(a.created_at || a.last_sold),
          total_desc: (a, b) => (b.total || b.net || 0) - (a.total || a.net || 0),
          qty_desc: (a, b) => (b.items || b.qty || 0) - (a.items || a.qty || 0),
          net_desc: (a, b) => (b.net || b.total || 0) - (a.net || a.total || 0),
        };
        const fn = sorters[sortKey] || sorters.date_desc;
        return [...rows].sort(fn);
      };

      return sorter(tab === "sales" ? filterSales : filterItems);
    };

    const paginate = (rows) => {
      const { page, pageSize } = reportState;
      const total = rows.length;
      const start = (page - 1) * pageSize;
      const end = Math.min(start + pageSize, total);
      return {
        rows: rows.slice(start, end),
        total,
        start: total ? start + 1 : 0,
        end,
      };
    };

    const renderKpis = () => {
      if (!kpiEl) return;
      const gross = reportData.sales.reduce((sum, r) => sum + Number(r.subtotal ?? r.total ?? 0), 0);
      const net = reportData.sales.reduce((sum, r) => sum + Number(r.total ?? r.net ?? 0), 0);
      const discount = reportData.sales.reduce((sum, r) => sum + Number(r.discount ?? 0), 0);
      const transactions = reportData.sales.length;
      const itemsSold = reportData.sales.reduce((sum, r) => sum + Number(r.items ?? 0), 0);
      const avgBasket = transactions ? net / transactions : 0;
      const avgItems = transactions ? itemsSold / transactions : 0;
      const payments = {};
      reportData.sales.forEach((sale) => {
        const key = normalizePayment(sale.payment || sale.payment_method);
        payments[key] = (payments[key] || 0) + (sale.total || sale.net || 0);
      });
      let topItem = null;
      reportData.items.forEach((item) => {
        const revenue = Number(item.net ?? item.gross ?? 0);
        if (!topItem || revenue > topItem.revenue) {
          topItem = {
            name: item.product || "Top item",
            revenue,
            qty: Number(item.qty ?? item.qty_sold ?? 0),
          };
        }
      });

      kpiSnapshot = {
        gross,
        net,
        discount,
        transactions,
        itemsSold,
        avgBasket,
        avgItems,
        payments,
        topItem,
      };

      const set = (sel, val) => {
        const el = kpiEl.querySelector(sel);
        if (el) el.textContent = val;
      };
      set("[data-kpi-total]", money(net));
      set("[data-kpi-transactions]", transactions.toLocaleString());
      set("[data-kpi-items]", itemsSold.toLocaleString());
      set("[data-kpi-basket]", money(avgBasket));
      set("[data-kpi-avg-items]", formatNumber(avgItems, 1));
      set("[data-kpi-discount]", money(discount));
      set(
        "[data-kpi-discount-rate]",
        gross > 0 && discount > 0 ? `${((discount / gross) * 100).toFixed(1)}% of sales` : "—"
      );
      set("[data-kpi-net]", money(net));
      set("[data-kpi-gross]", money(gross));

      if (paymentsEl) {
        const keys = ["cash", "qr", "card", "transfer", "mobile_money"];
        const rows = keys.map((key) => ({
          key,
          amount: payments[key] || 0,
          label: paymentLabels[key] || key,
        }));
        const otherAmount = Object.entries(payments).reduce((sum, [key, val]) => {
          if (keys.includes(key)) return sum;
          return sum + val;
        }, 0);
        if (otherAmount > 0) {
          rows.push({ key: "other", amount: otherAmount, label: "Other" });
        }
        const hasPayments = rows.some((row) => row.amount > 0);
        paymentsEl.innerHTML = hasPayments
          ? rows
              .map(
                (row) =>
                  `<div class="kpi-breakdown-row"><span>${row.label}</span><span>${money(row.amount)}</span></div>`
              )
              .join("")
          : '<div class="kpi-breakdown-empty">No payments recorded</div>';
      }

      if (topNameEl) {
        const topAmountEl = kpiEl.querySelector("[data-kpi-top-amount]");
        const topQtyEl = kpiEl.querySelector("[data-kpi-top-qty]");
        if (!topItem) {
          topNameEl.textContent = "No sales yet";
          if (topAmountEl) topAmountEl.textContent = "—";
          if (topQtyEl) topQtyEl.textContent = "";
        } else {
          topNameEl.textContent = topItem.name;
          if (topAmountEl) topAmountEl.textContent = money(topItem.revenue);
          if (topQtyEl) topQtyEl.textContent = `${formatNumber(topItem.qty || 0, 0)} pcs`;
        }
      }

      renderComparison();
      syncSecondaryVisibility();
    };

    const ensureKpiSnapshot = () => {
      if (!kpiSnapshot) renderKpis();
      return kpiSnapshot;
    };

    const renderSalesCard = (row) => {
      const date = fmtDate(row.created_at);
      const statusClass = `status-${row.status || "completed"}`;
      const payClass = `payment-${row.payment || "cash"}`;
      return `
        <article class="report-card">
          <div class="report-row">
            <div class="report-meta">
              <div class="report-title">${row.receipt} • ${row.cashier}</div>
              <div class="report-sub">${date}</div>
            </div>
            <div class="report-money">${money(row.total)}</div>
          </div>
          <div class="report-grid">
            <div class="report-stat"><div class="label">Items</div><div class="value">${row.items}</div></div>
            <div class="report-stat"><div class="label">Subtotal</div><div class="value">${money(row.subtotal)}</div></div>
            <div class="report-stat"><div class="label">Discount</div><div class="value">${money(row.discount)}</div></div>
            <div class="report-stat"><div class="label">Tax</div><div class="value">${money(row.tax)}</div></div>
            <div class="report-stat"><div class="label">Payment</div><div class="value"><span class="badge ${payClass}">${row.payment}</span></div></div>
            <div class="report-stat"><div class="label">Status</div><div class="value"><span class="badge ${statusClass}">${row.status}</span></div></div>
          </div>
        </article>
      `;
    };

    const renderItemsTable = (rows) => {
      const header = `
        <table class="report-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>SKU</th>
              <th>Category</th>
              <th>Cost</th>
              <th>Price</th>
              <th>Qty Sold</th>
              <th>Gross Sales</th>
              <th>Discount</th>
              <th>Net Sales</th>
              <th>Cashier</th>
              <th>Last Sold</th>
            </tr>
          </thead>
          <tbody>
      `;
      const body = rows
        .map((row) => {
          const date = fmtDate(row.last_sold);
          const cashierFull = (row.cashier || "").trim() || "Multiple";
          const cashierTitle = escapeAttr(cashierFull);
          const cashierCopy = escapeHtml(cashierFull);
          return `
            <tr>
              <td>${row.product}</td>
              <td>${row.sku}</td>
              <td>${row.category}</td>
              <td>${money(row.cost || 0)}</td>
              <td>${money(row.unit_price)}</td>
              <td>${row.qty}</td>
              <td>${money(row.gross)}</td>
              <td>${money(row.discount)}</td>
              <td>${money(row.net)}</td>
              <td><span class="cashier-list" title="${cashierTitle}">${cashierCopy}</span></td>
              <td>${date}</td>
            </tr>
          `;
        })
        .join("");
      const footer = `
          </tbody>
        </table>
      `;
      return header + body + footer;
    };

    const renderList = () => {
      if (!listEl) return;
      const rows = activeRows();
      const page = paginate(rows);
      renderKpis();

      if (resultsSection) {
        resultsSection.style.display = reportState.tab === "sales" ? "none" : "";
      }

      if (reportState.tab === "sales") {
        listEl.innerHTML = "";
        if (paginationEl) paginationEl.style.display = "none";
        return;
      }

      if (paginationEl) paginationEl.style.display = "";
      if (paginationCopy) {
        paginationCopy.textContent = `Showing ${page.start}–${page.end} of ${page.total}`;
      }
      if (prevBtn) prevBtn.disabled = reportState.page <= 1;
      if (nextBtn) nextBtn.disabled = page.end >= page.total;

      if (!page.rows.length) {
        listEl.innerHTML = `
          <div class="empty-card">
            <div class="empty-icon">📄</div>
            <div>No results for this date range</div>
            <div class="microcopy">Try clearing filters or adjusting the date range.</div>
          </div>
        `;
        return;
      }

      listEl.innerHTML = renderItemsTable(page.rows);
    };

    const refresh = () => {
      renderList();
      setLoading(false);
    };

    const normalizeSales = (rows) =>
      (Array.isArray(rows) ? rows : []).map((r) => {
        const payment = (r.payment_method || r.payment || "").toLowerCase() || "cash";
        const status = (r.status || "completed").toLowerCase();
        return {
          id: r.id || r.transaction_id || r.receipt_no || crypto.randomUUID?.() || String(Math.random()),
          receipt: r.receipt_no || r.transaction_id || "—",
          created_at: r.paid_at || r.created_at || null,
          cashier: r.cashier_username || r.cashier_name || "Unknown",
          items: r.items || r.qty_items || 0,
          subtotal: r.subtotal_cents ?? 0,
          discount: r.discount_cents ?? 0,
          tax: r.tax_cents ?? 0,
          total: r.total_cents ?? 0,
          payment,
          status,
        };
      });

    const normalizeItems = (rows) =>
      (Array.isArray(rows) ? rows : []).map((r) => {
        const asNumber = (val) => {
          const n = Number(val);
          return Number.isFinite(n) ? n : 0;
        };
        const cashiersRaw = String(r.cashiers ?? r.cashier ?? "").trim();
        const cashier = cashiersRaw !== "" ? cashiersRaw : "Multiple";
        return {
          product: r.product || "",
          sku: r.sku || "",
          category: r.category || "Uncategorized",
          cost: asNumber(r.cost_cents ?? r.avg_cost_cents ?? 0),
          unit_price: asNumber(r.avg_price_cents ?? r.unit_price_cents ?? 0),
          qty: asNumber(r.qty_sold ?? 0),
          gross: asNumber(r.gross_cents ?? 0),
          discount: asNumber(r.discount_cents ?? 0),
          net: asNumber(r.net_cents ?? r.gross_cents ?? 0),
          cashier: cashier,
          cashier_full: cashier,
          last_sold: r.last_sold || null,
        };
      });

    const buildQuery = (overrides = {}) => {
      const f = { ...reportState.filters, ...overrides };
      const params = new URLSearchParams();
      if (f.from) params.set("from", f.from);
      if (f.to) params.set("to", f.to);
      if (f.cashier) params.set("cashier", f.cashier);
      if (f.payment) params.set("payment", f.payment);
      params.set("aggregate", f.aggregate === false ? "0" : "1");
      return params.toString() ? `?${params.toString()}` : "";
    };

    const loadComparison = async () => {
      const range = computePreviousRange();
      comparisonState.loading = true;
      renderComparison();
      if (!range || !kpiSnapshot) {
        comparisonState.loading = false;
        comparisonState.percent = null;
        comparisonState.previousNet = null;
        renderComparison();
        return;
      }
      try {
        const qs = buildQuery({ from: range.from, to: range.to });
        const res = await fetch(`/api/sales${qs}`);
        const json = await res.json().catch(() => ({}));
        const rows = res.ok && Array.isArray(json.sales) ? normalizeSales(json.sales) : [];
        const prevNet = rows.reduce((sum, r) => sum + (r.total || r.net || 0), 0);
        comparisonState.previousNet = prevNet;
        comparisonState.percent = prevNet > 0 ? ((kpiSnapshot.net - prevNet) / prevNet) * 100 : null;
      } catch (e) {
        comparisonState.previousNet = null;
        comparisonState.percent = null;
      } finally {
        comparisonState.loading = false;
        renderComparison();
      }
    };

    const loadReportsData = async () => {
      try {
        setLoading(true);
        const qs = buildQuery();
        const [salesRes, itemsRes] = await Promise.all([
          fetch(`/api/sales${qs}`),
          fetch(`/api/report-items${qs}`),
        ]);
        const salesJson = await salesRes.json().catch(() => ({}));
        const itemsJson = await itemsRes.json().catch(() => ({}));
        if (salesRes.ok && Array.isArray(salesJson.sales)) {
          reportData.sales = normalizeSales(salesJson.sales);
        } else {
          reportData.sales = [];
          showToast(salesJson?.error || "Could not load sales.", "error");
        }
        const itemsArray = Array.isArray(itemsJson.items) ? itemsJson.items : [];
        reportData.items = normalizeItems(itemsArray);
        if (!itemsRes.ok) {
          showToast(itemsJson?.error || "Could not load items report.", "error");
        }
      } catch (e) {
        reportData.sales = [];
        reportData.items = [];
        showToast("Could not load reports.", "error");
      } finally {
        refresh();
        loadComparison();
      }
    };

    const updateSummary = () => {
      if (summaryText) {
        summaryText.textContent = getRangeCopy();
      }
      if (tabLabel) {
        tabLabel.textContent = reportState.tab === "sales" ? "Sales Summary" : "Items Sold";
      }
      if (groupToggle) {
        groupToggle.style.display = reportState.tab === "items" ? "inline-flex" : "none";
      }
    };

    const setDefaults = () => {
      const today = new Date();
      const start = new Date(today.getFullYear(), 0, 1);
      const startIso = start.toLocaleDateString("en-CA");
      const endIso = today.toLocaleDateString("en-CA");
      if (dateFromInput && !dateFromInput.value) dateFromInput.value = startIso;
      if (dateToInput && !dateToInput.value) dateToInput.value = endIso;
      reportState.filters.from = dateFromInput?.value || startIso;
      reportState.filters.to = dateToInput?.value || endIso;
    };

    const applyFilters = () => {
      const today = new Date();
      const startOfYear = new Date(today.getFullYear(), 0, 1).toLocaleDateString("en-CA");
      const todayIso = today.toLocaleDateString("en-CA");
      const fromVal = dateFromInput?.value || startOfYear;
      const toVal = dateToInput?.value || todayIso;
      if (dateFromInput && !dateFromInput.value) dateFromInput.value = fromVal;
      if (dateToInput && !dateToInput.value) dateToInput.value = toVal;

      reportState.page = 1;
      reportState.filters.from = fromVal;
      reportState.filters.to = toVal;
      hasApplied = true;
      updateSummary();
      loadReportsData();
    };

    const clearFilters = () => {
      const today = new Date();
      const startOfYear = new Date(today.getFullYear(), 0, 1).toLocaleDateString("en-CA");
      const todayIso = today.toLocaleDateString("en-CA");
      if (dateFromInput) dateFromInput.value = startOfYear;
      if (dateToInput) dateToInput.value = todayIso;
      reportState.filters = {
        from: startOfYear,
        to: todayIso,
        aggregate: Boolean(groupCheckbox?.checked ?? true),
      };
      reportState.page = 1;
      hasApplied = false;
      reportData.sales = [];
      reportData.items = [];
      updateSummary();
      refresh();
      loadReportsData();
    };

    tabButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const tab = btn.getAttribute("data-report-tab");
        if (!tab) return;
        tabButtons.forEach((b) => b.classList.toggle("active", b === btn));
        reportState.tab = tab;
        reportState.page = 1;
        if (tab === "sales") {
          reportState.sortBy = "date_desc";
        } else {
          reportState.sortBy = "net_desc";
        }
        updateSummary();
        refresh();
      });
    });

    prevBtn?.addEventListener("click", () => {
      if (reportState.page > 1) {
        reportState.page -= 1;
        refresh();
      }
    });
    nextBtn?.addEventListener("click", () => {
      const total = activeRows().length;
      const maxPage = Math.max(1, Math.ceil(total / reportState.pageSize));
      if (reportState.page < maxPage) {
        reportState.page += 1;
        refresh();
      }
    });

    groupCheckbox?.addEventListener("change", () => {
      reportState.filters.aggregate = Boolean(groupCheckbox.checked);
      reportState.page = 1;
      loadReportsData();
    });
    filterApplyBtns.forEach((btn) => btn.addEventListener("click", applyFilters));
    filterClearBtns.forEach((btn) => btn.addEventListener("click", clearFilters));
    const desktopMq = window.matchMedia("(min-width: 1024px)");
    const mqHandler = () => syncSecondaryVisibility();
    if (desktopMq?.addEventListener) {
      desktopMq.addEventListener("change", mqHandler);
    } else if (desktopMq?.addListener) {
      desktopMq.addListener(mqHandler);
    }
    kpiToggle?.addEventListener("click", () => {
      kpiExpanded = !kpiExpanded;
      syncSecondaryVisibility();
    });
    syncSecondaryVisibility();

    downloadBtn?.addEventListener("click", () => {
      if (downloadSheet) downloadSheet.classList.add("is-open");
    });
    downloadClose?.addEventListener("click", () => downloadSheet?.classList.remove("is-open"));
    downloadSheet?.addEventListener("click", (e) => {
      if (e.target === downloadSheet) downloadSheet.classList.remove("is-open");
    });

    const csvEscape = (value) => {
      if (value === null || value === undefined) return "";
      const s = String(value);
      if (/[",\n]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
      return s;
    };
    const toCsv = (rows, columns, opts = { bom: true }) => {
      const header = columns.map((c) => csvEscape(c.label)).join(",");
      const lines = rows.map((row) =>
        columns
          .map((c) => {
            const v = typeof c.value === "function" ? c.value(row) : row[c.value];
            return csvEscape(v);
          })
          .join(",")
      );
      const bom = opts?.bom === false ? "" : "\ufeff";
      return bom + [header, ...lines].join("\n");
    };
    const downloadBlob = (content, filename, mime = "text/csv") => {
      const blob = new Blob([content], { type: `${mime};charset=utf-8;` });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    };
    const centsToNumber = (cents) => Number((Number(cents ?? 0) / 100).toFixed(2));
    const dateCsv = (val) => fmtDateShort(val);
    const buildRangeSlug = () => {
      const { from, to } = reportState.filters;
      return `${from || "all"}_to_${to || "all"}`;
    };

    const downloadReports = async (type) => {
      try {
        showToast("Preparing downloads…", "info");
        await loadReportsData();

        const salesRows = reportData.sales.map((r) => ({
          receipt: r.receipt,
          date: dateCsv(r.created_at),
          cashier: r.cashier,
          items: r.items,
          subtotal: centsToNumber(r.subtotal),
          discount: centsToNumber(r.discount),
          tax: centsToNumber(r.tax),
          total: centsToNumber(r.total),
          payment: r.payment,
          status: r.status,
        }));
        const snap = ensureKpiSnapshot();
        const discountRate = snap && snap.gross > 0 ? (snap.discount / snap.gross) * 100 : null;
        const comparisonCopy = (() => {
          const pct = comparisonState.percent;
          if (pct === null || Number.isNaN(pct)) return "No previous period";
          const rounded = pct.toFixed(1);
          const prefix = pct > 0 ? "+" : "";
          return `${prefix}${rounded}% vs previous period`;
        })();
        const kpiRows = snap
          ? [
              { metric: "Total Sales (Net)", value: money(snap.net || 0) },
              { metric: "Gross Sales", value: money(snap.gross || 0) },
              {
                metric: "Discounts Given",
                value: money(snap.discount || 0),
                notes: discountRate !== null ? `${discountRate.toFixed(1)}% of gross` : "",
              },
              { metric: "Transactions", value: (snap.transactions || 0).toLocaleString() },
              { metric: "Items Sold", value: (snap.itemsSold || 0).toLocaleString() },
              { metric: "Avg Basket Value", value: money(snap.avgBasket || 0) },
              { metric: "Avg Items per Transaction", value: formatNumber(snap.avgItems || 0, 1) },
              ...["cash", "qr", "card", "transfer", "mobile_money"].map((key) => ({
                metric: `Payment - ${paymentLabels[key] || key}`,
                value: money(snap.payments?.[key] || 0),
              })),
              ...(() => {
                const otherTotal = Object.entries(snap.payments || {}).reduce((sum, [key, val]) => {
                  if (["cash", "qr", "card", "transfer", "mobile_money"].includes(key)) return sum;
                  return sum + (val || 0);
                }, 0);
                return otherTotal > 0
                  ? [{ metric: "Payment - Other", value: money(otherTotal) }]
                  : [];
              })(),
              snap.topItem
                ? {
                    metric: "Top Item",
                    value: snap.topItem.name,
                    notes: `${money(snap.topItem.revenue)} (${formatNumber(snap.topItem.qty || 0, 0)} pcs)`,
                  }
                : { metric: "Top Item", value: "—", notes: "" },
              { metric: "Period Comparison", value: comparisonCopy },
            ]
          : [];
        const itemsRows = reportData.items.map((r) => ({
          product: r.product,
          sku: r.sku,
          category: r.category,
          cost: centsToNumber(r.cost),
          price: centsToNumber(r.unit_price),
          qty: r.qty,
          gross: centsToNumber(r.gross),
          discount: centsToNumber(r.discount),
          net: centsToNumber(r.net),
          cashier: r.cashier,
          last_sold: dateCsv(r.last_sold),
        }));

        if (!salesRows.length && !itemsRows.length) {
          showToast("No rows to download for this range.", "error");
          return;
        }

        const ext = "csv"; // Excel-friendly CSV to avoid invalid .xlsx content errors
        const range = buildRangeSlug();
        const itemsSuffix = reportState.filters.aggregate ? "aggregated" : "detailed";

        if (salesRows.length) {
          const salesKpiCsv =
            kpiRows && kpiRows.length
              ? toCsv(
                  kpiRows,
                  [
                    { label: "Metric", value: "metric" },
                    { label: "Value", value: "value" },
                    { label: "Notes", value: "notes" },
                  ],
                  { bom: true }
                )
              : "";
          const salesDetailCsv = toCsv(
            salesRows,
            [
              { label: "Receipt / Transaction", value: "receipt" },
              { label: "Date", value: "date" },
              { label: "Cashier", value: "cashier" },
              { label: "Items", value: "items" },
              { label: "Subtotal", value: "subtotal" },
              { label: "Discount", value: "discount" },
              { label: "Tax", value: "tax" },
              { label: "Total", value: "total" },
              { label: "Payment", value: "payment" },
              { label: "Status", value: "status" },
            ],
            { bom: !salesKpiCsv }
          );
          const salesCsv = salesKpiCsv ? `${salesKpiCsv}\n\n${salesDetailCsv}` : salesDetailCsv;
          downloadBlob(salesCsv, `plughub_report_sales_summary_${range}.${ext}`);
        }

        if (itemsRows.length) {
          const itemsCsv = toCsv(itemsRows, [
            { label: "Product", value: "product" },
            { label: "SKU", value: "sku" },
            { label: "Category", value: "category" },
            { label: "Cost", value: "cost" },
            { label: "Price", value: "price" },
            { label: "Qty Sold", value: "qty" },
            { label: "Gross Sales", value: "gross" },
            { label: "Discount", value: "discount" },
            { label: "Net Sales", value: "net" },
            { label: "Cashier(s)", value: "cashier" },
            { label: "Last Sold", value: "last_sold" },
          ]);
          downloadBlob(itemsCsv, `plughub_report_items_sold_${itemsSuffix}_${range}.${ext}`);
        }

        showToast("Downloads ready", "success");
      } catch (err) {
        showToast("Could not download reports.", "error");
      } finally {
        downloadSheet?.classList.remove("is-open");
      }
    };

    downloadCsv?.addEventListener("click", () => downloadReports("csv"));
    downloadXlsx?.addEventListener("click", () => downloadReports("xlsx"));

    helpBtn?.addEventListener("click", () => {
      showToast("Use the date range to scope the reports.", "info");
    });

    setDefaults();
    reportState.filters.aggregate = Boolean(groupCheckbox?.checked ?? true);
    applyFilters();
  }

  applyRoleGuards();
  if (els.posScreen) {
    loadData();
  }
})();

(() => {
  const root = document.querySelector("[data-login-screen]");
  if (!root) return;

  const usernameInput = root.querySelector("[data-login-username]");
  const passwordInput = root.querySelector("[data-login-password]");
  const fillBtn = root.querySelector("[data-demo-fill]");
  const copyBtn = root.querySelector("[data-demo-copy]");
  const resetBtn = root.querySelector("[data-demo-reset]");
  const demoUsernameInput = root.querySelector("[data-demo-username]");
  const demoPasswordInput = root.querySelector("[data-demo-password]");
  const loginToggle = root.querySelector("[data-toggle-login-password]");
  const demoToggle = root.querySelector("[data-toggle-demo-password]");

  const demoUsername = "admin";
  const demoPassword = "Admin123!";

  const ensureToastStack = () => {
    let stack = document.querySelector(".toast-stack");
    if (!stack) {
      stack = document.createElement("div");
      stack.className = "toast-stack";
      document.body.appendChild(stack);
    }
    return stack;
  };

  const showToast = (message, variant = "info") => {
    const stack = ensureToastStack();
    const toast = document.createElement("div");
    toast.className = `toast toast-${variant}`;
    toast.textContent = message;
    stack.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("show"));
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 200);
    }, 2600);
  };

  const applyDemoCredentials = () => {
    if (usernameInput) usernameInput.value = demoUsername;
    if (passwordInput) passwordInput.value = demoPassword;
    passwordInput?.focus();
    showToast("Demo credentials applied", "success");
  };

  const copyToClipboard = async (text) => {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return true;
    }
    const helper = document.createElement("textarea");
    helper.value = text;
    helper.setAttribute("readonly", "true");
    helper.style.position = "fixed";
    helper.style.opacity = "0";
    document.body.appendChild(helper);
    helper.select();
    const ok = document.execCommand("copy");
    helper.remove();
    return ok;
  };

  const handleCopy = async () => {
    const text = `Username: ${demoUsername} Password: ${demoPassword}`;
    try {
      const ok = await copyToClipboard(text);
      showToast(ok ? "Credentials copied" : "Copy failed", ok ? "success" : "error");
    } catch (err) {
      showToast("Copy failed", "error");
    }
  };

  const resetFields = () => {
    if (usernameInput) usernameInput.value = "";
    if (passwordInput) passwordInput.value = "";
    usernameInput?.focus();
  };

  const togglePassword = (input, button) => {
    if (!input || !button) return;
    const shouldShow = input.type === "password";
    input.type = shouldShow ? "text" : "password";
    button.textContent = shouldShow ? "Hide" : "Show";
    button.setAttribute("aria-pressed", shouldShow ? "true" : "false");
  };

  if (demoUsernameInput) demoUsernameInput.value = demoUsername;
  if (demoPasswordInput) demoPasswordInput.value = demoPassword;

  fillBtn?.addEventListener("click", applyDemoCredentials);
  copyBtn?.addEventListener("click", handleCopy);
  resetBtn?.addEventListener("click", resetFields);
  loginToggle?.addEventListener("click", () => togglePassword(passwordInput, loginToggle));
  demoToggle?.addEventListener("click", () => togglePassword(demoPasswordInput, demoToggle));
})();
