(() => {
  const money = (cents) => (cents / 100).toLocaleString(undefined, { style: "currency", currency: "NGN" });

  const products = [
    { id: 1, name: "Coke 50cl", price_cents: 45000, sku: "COKE-50" },
    { id: 2, name: "Bottled Water", price_cents: 25000, sku: "WATER-50" },
    { id: 3, name: "Chin-Chin", price_cents: 60000, sku: "SNACK-01" },
    { id: 4, name: "Bread (Small)", price_cents: 70000, sku: "BREAD-S" },
    { id: 5, name: "Milk 1L", price_cents: 180000, sku: "MILK-1L" },
    { id: 6, name: "Sugar 500g", price_cents: 90000, sku: "SUGAR-500" },
  ];

  const state = {
    cart: new Map(), // productId -> qty
    query: "",
  };

  const els = {
    productGrid: document.querySelector("[data-products]"),
    cartList: document.querySelector("[data-cart]"),
    total: document.querySelector("[data-total]"),
    count: document.querySelector("[data-count]"),
    query: document.querySelector("[data-search]"),
    clear: document.querySelector("[data-clear]"),
  };

  const getQty = (id) => state.cart.get(id) ?? 0;
  const setQty = (id, qty) => {
    if (qty <= 0) state.cart.delete(id);
    else state.cart.set(id, qty);
  };

  const filtered = () => {
    const q = state.query.trim().toLowerCase();
    if (!q) return products;
    return products.filter((p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q));
  };

  const totals = () => {
    let count = 0;
    let total = 0;
    for (const p of products) {
      const qty = getQty(p.id);
      if (!qty) continue;
      count += qty;
      total += qty * p.price_cents;
    }
    return { count, total };
  };

  const renderProducts = () => {
    if (!els.productGrid) return;
    els.productGrid.innerHTML = filtered()
      .map((p) => {
        const qty = getQty(p.id);
        return `
        <div class="tile">
          <div class="name">${escapeHtml(p.name)}</div>
          <div class="meta">
            <span>${escapeHtml(p.sku)}</span>
            <span class="price">${money(p.price_cents)}</span>
          </div>
          <button class="btn btn-primary" data-add="${p.id}">
            ${qty ? `Add (+1) • In cart: ${qty}` : "Add to cart"}
          </button>
        </div>`;
      })
      .join("");
  };

  const renderCart = () => {
    if (!els.cartList) return;
    const lines = [];
    for (const p of products) {
      const qty = getQty(p.id);
      if (!qty) continue;
      lines.push(`
        <div class="line">
          <div class="left">
            <div class="title">${escapeHtml(p.name)}</div>
            <div class="sub">${escapeHtml(p.sku)} • ${money(p.price_cents)} each</div>
          </div>
          <div class="qty">
            <button class="btn btn-ghost" data-dec="${p.id}">−</button>
            <div class="num">${qty}</div>
            <button class="btn btn-ghost" data-inc="${p.id}">+</button>
          </div>
        </div>
      `);
    }
    els.cartList.innerHTML = lines.length ? lines.join("") : `<div class="pill">Cart is empty</div>`;

    const t = totals();
    if (els.total) els.total.textContent = money(t.total);
    if (els.count) els.count.textContent = `${t.count} item${t.count === 1 ? "" : "s"}`;
  };

  const renderAll = () => {
    renderProducts();
    renderCart();
  };

  const escapeHtml = (s) =>
    String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  document.addEventListener("click", (e) => {
    const t = e.target;
    if (!(t instanceof HTMLElement)) return;
    const addId = t.getAttribute("data-add");
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
    }
  });

  els.query?.addEventListener("input", (e) => {
    state.query = e.target.value ?? "";
    renderProducts();
  });

  els.clear?.addEventListener("click", () => {
    state.cart.clear();
    renderAll();
  });

  renderAll();
})();

