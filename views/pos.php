<?php
declare(strict_types=1);

ob_start();
?>
<header class="topbar">
  <div class="brand">
    <h1><?= e($title ?? 'PlugHub POS') ?></h1>
    <div class="pill">Mobile-first</div>
  </div>
  <div class="search">
    <input class="input" data-search placeholder="Search (name or SKU)" inputmode="search" />
    <button class="btn btn-ghost" type="button" data-clear>Clear</button>
  </div>
</header>

<main class="content">
  <section class="panel">
    <h2>Products</h2>
    <div class="grid" data-products></div>
  </section>

  <section class="panel">
    <h2>Cart</h2>
    <div class="cart" data-cart></div>
  </section>
</main>

<div class="cartbar">
  <div class="cartbar-inner">
    <div class="cart-summary">
      <div>
        <div class="small" data-count>0 items</div>
        <div class="total" data-total>₦0.00</div>
      </div>
      <button class="btn btn-primary" type="button">Checkout</button>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

