-- Seed 50 products with full data for a tenant
-- Usage: psql -U plughub -d plughub_possystem -v tenant_slug='default' -f scripts/seed-tenant-products.sql

\set ON_ERROR_STOP on
\echo Seeding products for tenant :tenant_slug

-- Resolve tenant id
with tid as (
  select id from tenants where slug = :'tenant_slug' limit 1
)
select case when not exists(select 1 from tid) then
  pg_sleep(0) -- no-op
end;

-- Ensure categories (idempotent)
with tid as (select id from tenants where slug = :'tenant_slug' limit 1)
insert into categories (tenant_id, name, active)
values
  ((select id from tid), 'Beverages', true),
  ((select id from tid), 'Bakery', true),
  ((select id from tid), 'Snacks', true),
  ((select id from tid), 'Accessories', true),
  ((select id from tid), 'Electronics', true)
on conflict (tenant_id, name) do update set active = excluded.active;

-- Wipe products + inventory for this tenant only
with tid as (select id from tenants where slug = :'tenant_slug' limit 1)
delete from inventory_movements where tenant_id in (select id from tid);
with tid as (select id from tenants where slug = :'tenant_slug' limit 1)
delete from products where tenant_id in (select id from tid);

-- Insert 50 products
with tid as (select id from tenants where slug = :'tenant_slug' limit 1),
cats as (
  select tenant_id, id, name,
         row_number() over (order by lower(name)) as rn
  from categories where tenant_id = (select id from tid)
),
ins as (
  insert into products (tenant_id, sku, name, category_id, price_cents, cost_cents, qty_on_hand, active)
  select
    (select id from tid) as tenant_id,
    format('SKU-%03s', gs) as sku,
    format('Product %s', gs) as name,
    (select id from cats where rn = ((gs - 1) % greatest((select count(*) from cats),1)) + 1),
    5000 + gs * 100,          -- price: varies
    3000 + gs * 80,           -- cost: varies
    0,
    true
  from generate_series(1,50) as gs
  returning id, tenant_id, price_cents, cost_cents
)
select count(*) as inserted from ins;

-- Seed inventory movements to set stock (random-ish, deterministic)
with tid as (select id from tenants where slug = :'tenant_slug' limit 1),
p as (
  select id, tenant_id, ((id % 25) + 5) as qty_seed from products where tenant_id = (select id from tid)
)
insert into inventory_movements (product_id, tenant_id, change_qty, reason, ref_type, ref_id, created_by)
select id, tenant_id, qty_seed, 'seed', 'script', null, null from p;

-- Align qty_on_hand with ledger sum
with tid as (select id from tenants where slug = :'tenant_slug' limit 1)
update products p
set qty_on_hand = coalesce(m.qty, 0)
from (
  select product_id, sum(change_qty) as qty
  from inventory_movements
  where tenant_id = (select id from tid)
  group by product_id
) m
where p.id = m.product_id and p.tenant_id = (select id from tid);

-- Summary
with tid as (select id from tenants where slug = :'tenant_slug' limit 1)
select
  count(*) as product_count,
  min(price_cents) as min_price,
  max(price_cents) as max_price,
  sum(qty_on_hand) as total_qty
from products
where tenant_id = (select id from tid);
