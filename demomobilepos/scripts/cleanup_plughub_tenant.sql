-- Cleanup script for Plughub Gadgets & Accessories tenant
-- Removes products, inventory movements, and categories for that tenant only.
-- Usage:
--   psql -U plughub -d plughub_possystem -f scripts/cleanup_plughub_tenant.sql

\set ON_ERROR_STOP on

do $$
declare
  tid bigint;
begin
  select id into tid from tenants where lower(name) = lower('Plughub Gadgets & Accessories') limit 1;
  if tid is null then
    raise exception 'Tenant not found: Plughub Gadgets & Accessories';
  end if;

  -- Clear inventory movements first (products delete will cascade, but do it explicitly for clarity)
  delete from inventory_movements where tenant_id = tid;

  -- Clear products (order_items.product_id is on delete set null, so safe)
  delete from products where tenant_id = tid;

  -- Clear categories (products.category_id is on delete set null, so safe)
  delete from categories where tenant_id = tid;

  raise notice 'Cleanup complete for tenant id %', tid;
end$$;

-- Summary after cleanup
with t as (select id from tenants where lower(name) = lower('Plughub Gadgets & Accessories') limit 1)
select
  (select count(*) from products where tenant_id = (select id from t)) as product_count,
  (select count(*) from categories where tenant_id = (select id from t)) as category_count,
  (select count(*) from inventory_movements where tenant_id = (select id from t)) as movement_count;
