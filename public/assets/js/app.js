(() => {
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
  };

  const getQty = (id) => state.cart.get(id) ?? 0;
  const setQty = (id, qty) => {
    if (qty <= 0) state.cart.delete(id);
    else state.cart.set(id, qty);
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
  };

  const renderCartSummary = () => {
    const t = totals();
    if (els.cartCount) els.cartCount.textContent = `${t.count} item${t.count === 1 ? "" : "s"}`;
    if (els.cartTotal) els.cartTotal.textContent = money(t.total);
    if (els.cartTotalDrawer) els.cartTotalDrawer.textContent = money(t.total);
    if (els.cartSubtotal) els.cartSubtotal.textContent = money(t.subtotal);
    if (els.cartDiscount) els.cartDiscount.textContent = `−${money(t.discount)}`;
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
            <button class="btn btn-ghost" data-dec="${p.id}">−</button>
            <div class="num">${qty}</div>
            <button class="btn btn-primary" data-inc="${p.id}">+</button>
          </div>
        </div>
      `);
    }
    els.cartList.innerHTML = lines.length ? lines.join("") : `<div class="pill">Cart is empty</div>`;
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
  els.cartViewBtn?.addEventListener("click", () => {
    renderCartDrawer();
    openCart();
  });
  els.cartCloseBtn?.addEventListener("click", closeCart);
  els.cartOverlay?.addEventListener("click", (e) => {
    if (e.target === els.cartOverlay) closeCart();
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
    const val = parseFloat(e.target.value);
    const cents = Number.isFinite(val) ? Math.round(val * 100) : 0;
    const t = totals();
    state.discount = Math.max(0, Math.min(t.subtotal, cents));
    renderCartSummary();
  });

  loadData();
})();
