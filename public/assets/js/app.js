(() => {
  const ctx = window.__APP_CTX__ || {};
  const ctxRole = String(ctx.role || "");
  const isReadonly = Boolean(ctx.is_readonly) || ctxRole.toLowerCase() === "readonly";

  const money = (cents) => (cents / 100).toLocaleString(undefined, { style: "currency", currency: "PHP" });

  const fallbackProducts = [
    { id: 1, name: "Coke 50cl", price_cents: 45000, sku: "COKE-50", category_id: 1 },
    { id: 2, name: "Bottled Water", price_cents: 25000, sku: "WATER-50", category_id: 1 },
    { id: 3, name: "Chin-Chin", price_cents: 60000, sku: "SNACK-01", category_id: 3 },
    { id: 4, name: "Bread (Small)", price_cents: 70000, sku: "BREAD-S", category_id: 2 },
    { id: 5, name: "Milk 1L", price_cents: 180000, sku: "MILK-1L", category_id: 4 },
    { id: 6, name: "Sugar 500g", price_cents: 90000, sku: "SUGAR-500", category_id: 4 },
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
    menuDropdown: document.querySelector("[data-menu-dropdown]"),
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
  };

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
        return `
        <div class="tile">
          <div class="title-row">
            <span class="dot" style="background:${catColor(p.category_id)}"></span>
            <div class="name">${escapeHtml(p.name)}</div>
          </div>
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
        if (data?.ok && Array.isArray(data.products) && data.products.length) {
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
      setQty(id, getQty(id) + 1);
      renderAll();
    } else if (incId) {
      const id = Number(incId);
      setQty(id, getQty(id) + 1);
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

  const openMenu = () => {
    if (!els.menuDropdown) return;
    els.menuDropdown.classList.add("active");
  };
  const closeMenu = () => {
    if (!els.menuDropdown) return;
    els.menuDropdown.classList.remove("active");
  };
  els.menuOpen?.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (els.menuDropdown?.classList.contains("active")) {
      closeMenu();
    } else {
      openMenu();
    }
  });
  document.addEventListener("click", (e) => {
    const target = e.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.closest("[data-menu-dropdown]")) return;
    if (target.closest("[data-menu-open]")) return;
    closeMenu();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeMenu();
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

  els.completeSale?.addEventListener("click", () => {
    if (guardSales()) return;
    const t = totals();
    const paid = state.cashAmount;
    const change = Math.max(0, paid - t.total);
    const method = state.paymentMethod === "cash" ? "Cash" : "QR Code";
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    const hh = String(now.getHours()).padStart(2, "0");
    const mm = String(now.getMinutes()).padStart(2, "0");
    const fullTxn = `${"PHGA"}${"PH"}${y}${m}${d}${hh}${mm}`;
    const shortTxn = `${fullTxn.slice(0, 6)}...${fullTxn.slice(-4)}`;
    const ts = now.toLocaleString(undefined, {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });

    if (els.completePaid) els.completePaid.textContent = money(paid);
    if (els.completeChange) els.completeChange.textContent = `Change Due ${money(change)}`;
    if (els.completeChangeDetail) els.completeChangeDetail.textContent = money(change);
    if (els.completeDiscount) els.completeDiscount.textContent = money(state.discount);
    if (els.completeItems) {
      const count = Array.from(state.cart.values()).reduce((a, b) => a + b, 0);
      els.completeItems.textContent = `${count}`;
    }
    if (els.completeMethod) els.completeMethod.textContent = method;
    if (els.completeMethodIcon) els.completeMethodIcon.textContent = method === "Cash" ? "💵" : "🔲";
    if (els.completeTxn) {
      els.completeTxn.textContent = shortTxn;
      els.completeTxn.dataset.fullTxn = fullTxn;
    }
    if (els.completeSaleTotal) els.completeSaleTotal.textContent = money(t.total);
    if (els.completeAmountReceived) els.completeAmountReceived.textContent = money(paid);
    if (els.completeChangeDue) els.completeChangeDue.textContent = money(change);
    if (els.completeSubtotal) els.completeSubtotal.textContent = money(t.subtotal);
    if (els.completeTs) els.completeTs.textContent = ts;
    if (els.completeCartList && els.completeCartTotal && els.completeCartCount) {
      const items = getCartItems();
      const rows = items.map((p) => {
        const line = p.qty * p.price_cents;
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
      els.completeCartTotal.textContent = money(t.total);
      const cartSubtotal = items.reduce((sum, p) => sum + p.qty * p.price_cents, 0);
      const cartDiscount = Math.max(0, cartSubtotal - t.total);
      if (els.completeCartSubtotal) els.completeCartSubtotal.textContent = money(cartSubtotal);
      if (els.completeCartDiscount) els.completeCartDiscount.textContent = money(cartDiscount);
      if (els.completeCartPaid) els.completeCartPaid.textContent = money(paid);
      if (els.completeCartChange) els.completeCartChange.textContent = money(change);
      if (els.completeCartMethod) els.completeCartMethod.textContent = method;
      const count = items.reduce((a, b) => a + b.qty, 0);
      els.completeCartCount.textContent = `${count} item${count === 1 ? "" : "s"}`;
    }
    closeCashSheet();
    if (els.checkoutScreen) els.checkoutScreen.classList.add("is-hidden");
    if (els.posScreen) els.posScreen.classList.add("is-hidden");
    if (els.saleComplete) els.saleComplete.classList.remove("is-hidden");
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
    renderAll();
    if (els.saleComplete) els.saleComplete.classList.add("is-hidden");
    if (els.checkoutScreen) els.checkoutScreen.classList.add("is-hidden");
    if (els.posScreen) els.posScreen.classList.remove("is-hidden");
  });

  els.printReceipt?.addEventListener("click", () => {
    alert("Printing receipt...");
  });
  els.sendReceipt?.addEventListener("click", () => {
    alert("Sending receipt...");
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

  applyRoleGuards();
  loadData();
})();
