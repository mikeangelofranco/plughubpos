-- Seed 50 products for "Plughub Gadgets & Accessories" with full fields
-- Usage:
--   psql -U plughub -d plughub_possystem -f scripts/seed_plughub_products.sql
-- Adjust DB user/name if different.

\set ON_ERROR_STOP on

do $$
declare
  tid bigint;
begin
  select id into tid
  from tenants
  where lower(name) = lower('Plughub Gadgets & Accessories')
  limit 1;

  if tid is null then
    raise exception 'Tenant not found: Plughub Gadgets & Accessories';
  end if;

  -- Clear existing data for this tenant
  delete from inventory_movements where tenant_id = tid;
  delete from products where tenant_id = tid;
  delete from categories where tenant_id = tid;

  -- Recreate categories (idempotent per tenant)
  insert into categories (tenant_id, name, active) values
    (tid, 'Beverages', true),
    (tid, 'Bakery', true),
    (tid, 'Snacks', true),
    (tid, 'Accessories', true),
    (tid, 'Electronics', true),
    (tid, 'Audio', true),
    (tid, 'Cables', true),
    (tid, 'Chargers', true),
    (tid, 'Kitchen', true),
    (tid, 'Home', true)
  on conflict (tenant_id, name) do update set active = excluded.active;

  -- Insert 50 products with cost/price/category and base qty_on_hand = 0
  with cats as (
    select id, row_number() over (order by lower(name)) as rn
    from categories
    where tenant_id = tid
  ),
  ins as (
    insert into products (
      tenant_id, sku, name, category_id, price_cents, cost_cents, qty_on_hand, active
    )
    select
      tid,
      format('PLG-%03s', gs) as sku,
      format('Product %s', gs) as name,
      (select id from cats where rn = ((gs - 1) % (select count(*) from cats)) + 1),
      15000 + (gs * 250) as price_cents,     -- price: 150.00 + steps
      9000 + (gs * 180)  as cost_cents,      -- cost: below price
      0,
      true
    from generate_series(1,50) as gs
    returning id, tenant_id, price_cents, cost_cents
  )
  select count(*) into tid from ins; -- reuse tid just to consume result; value not used

  -- Seed inventory via movements (sets qty_on_hand via ledger)
  with p as (
    select id, ((id % 30) + 8) as qty_seed
    from products
    where tenant_id = tid
  )
  insert into inventory_movements (
    product_id, tenant_id, change_qty, reason, ref_type, ref_id, created_by
  )
  select id, tid, qty_seed, 'seed', 'script', null, null from p;

  -- Align qty_on_hand with ledger sum
  update products p
  set qty_on_hand = coalesce(m.qty, 0)
  from (
    select product_id, sum(change_qty) as qty
    from inventory_movements
    where tenant_id = tid
    group by product_id
  ) m
  where p.id = m.product_id and p.tenant_id = tid;

  raise notice 'Seed completed for tenant id %', tid;
end$$;

-- Summary
with t as (select id from tenants where lower(name) = lower('Plughub Gadgets & Accessories') limit 1)
select
  (select count(*) from products where tenant_id = (select id from t)) as product_count,
  (select count(*) from categories where tenant_id = (select id from t)) as category_count,
  (select count(*) from inventory_movements where tenant_id = (select id from t)) as movement_count,
  (select sum(qty_on_hand) from products where tenant_id = (select id from t)) as total_qty;
