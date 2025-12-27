-- ----------------------------------------------------------------------------
-- Core tenants + users
-- ----------------------------------------------------------------------------
create table if not exists tenants (
  id bigserial primary key,
  name text not null,
  slug text not null unique,
  active boolean not null default true,
  address text null,
  contact_number text null,
  created_at timestamptz not null default now()
);

create unique index if not exists tenants_lower_name_idx on tenants (lower(name));

alter table tenants add column if not exists address text;
alter table tenants add column if not exists contact_number text;

do $$
begin
  insert into tenants (name, slug, active)
  values ('Default Tenant', 'default', true)
  on conflict (slug) do update
    set name = excluded.name,
        active = excluded.active;
end
$$;


create table if not exists users (
  id bigserial primary key,
  tenant_id bigint null references tenants(id) on delete set null,
  username text not null,
  password_hash text not null,
  role text not null default 'Cashier',
  full_name text null,
  contact_number text null,
  active boolean not null default true,
  created_at timestamptz not null default now(),
  unique (tenant_id, username)
);

alter table users add column if not exists tenant_id bigint references tenants(id) on delete set null;
alter table users add column if not exists full_name text;
alter table users add column if not exists contact_number text;
alter table users alter column role set default 'Cashier';
alter table users alter column active set default true;

do $$
begin
  if exists (select 1 from pg_constraint where conname = 'users_role_chk') then
    alter table users drop constraint users_role_chk;
  end if;
exception when others then
  null;
end
$$;

alter table users add constraint users_role_chk check (role in ('Admin','Manager','Cashier','Readonly'));

do $$
begin
  if exists (select 1 from pg_constraint where conname = 'users_username_key') then
    alter table users drop constraint users_username_key;
  end if;
exception when others then
  null;
end
$$;

do $$
begin
  if not exists (select 1 from pg_constraint where conname in ('users_tenant_username_key', 'users_tenant_id_username_key')) then
    alter table users add constraint users_tenant_username_key unique (tenant_id, username);
  end if;
exception when others then
  null;
end
$$;

do $$
declare
  default_tid bigint;
begin
  select id into default_tid from tenants where slug = 'default' limit 1;
  update users set role = 'Readonly' where role = 'User';
  if default_tid is not null then
    update users set tenant_id = default_tid where tenant_id is null;
  end if;
end
$$;

-- Default admin user (Admin role; unlocked across tenants)
--   username: admin
--   password: Cablet0w
insert into users (tenant_id, username, password_hash, role, active)
values ((select id from tenants where slug = 'default' limit 1), 'admin', '$2y$10$VgP/nNQc/.QaisFpgfrJQemVobXWUrjJbnTG.KdJ4hiCdkdTH863O', 'Admin', true)
on conflict (tenant_id, username) do update
set password_hash = excluded.password_hash,
    role = excluded.role,
    active = excluded.active;

-- Sample tenant-scoped manager + cashier for quick testing
insert into users (tenant_id, username, password_hash, role, active)
values
  ((select id from tenants where slug = 'default' limit 1), 'manager', '$2y$10$VgP/nNQc/.QaisFpgfrJQemVobXWUrjJbnTG.KdJ4hiCdkdTH863O', 'Manager', true),
  ((select id from tenants where slug = 'default' limit 1), 'cashier', '$2y$10$VgP/nNQc/.QaisFpgfrJQemVobXWUrjJbnTG.KdJ4hiCdkdTH863O', 'Cashier', true),
  ((select id from tenants where slug = 'default' limit 1), 'readonly', '$2y$10$VgP/nNQc/.QaisFpgfrJQemVobXWUrjJbnTG.KdJ4hiCdkdTH863O', 'Readonly', true)
on conflict (tenant_id, username) do update
set password_hash = excluded.password_hash,
    role = excluded.role,
    active = excluded.active;

-- ----------------------------------------------------------------------------
-- Catalog (categories, products)
-- ----------------------------------------------------------------------------
create table if not exists categories (
  id bigserial primary key,
  tenant_id bigint null references tenants(id) on delete set null,
  name text not null,
  active boolean not null default true,
  created_at timestamptz not null default now()
);

alter table categories add column if not exists tenant_id bigint references tenants(id) on delete set null;
alter table categories add column if not exists active boolean not null default true;

do $$
begin
  if exists (select 1 from pg_constraint where conname = 'categories_name_key') then
    alter table categories drop constraint categories_name_key;
  end if;
exception when others then
  null;
end
$$;

do $$
begin
  if not exists (select 1 from pg_constraint where conname in ('categories_tenant_name_key', 'categories_tenant_id_name_key')) then
    alter table categories add constraint categories_tenant_name_key unique (tenant_id, name);
  end if;
exception when others then
  null;
end
$$;

create table if not exists products (
  id bigserial primary key,
  tenant_id bigint null references tenants(id) on delete set null,
  sku text not null,
  name text not null,
  category_id bigint null references categories(id) on delete set null,
  price_cents integer not null check (price_cents >= 0),
  cost_cents integer not null default 0 check (cost_cents >= 0),
  qty_on_hand integer not null default 0 check (qty_on_hand >= 0),
  active boolean not null default true,
  created_at timestamptz not null default now()
);

alter table products add column if not exists tenant_id bigint references tenants(id) on delete set null;
alter table products add column if not exists qty_on_hand integer not null default 0;
alter table products add column if not exists cost_cents integer not null default 0;

do $$
begin
  if exists (select 1 from pg_constraint where conname = 'products_sku_key') then
    alter table products drop constraint products_sku_key;
  end if;
exception when others then
  null;
end
$$;

do $$
begin
  if not exists (select 1 from pg_constraint where conname in ('products_tenant_sku_key', 'products_tenant_id_sku_key')) then
    alter table products add constraint products_tenant_sku_key unique (tenant_id, sku);
  end if;
exception when others then
  null;
end
$$;

create index if not exists products_active_idx on products(active);
create index if not exists products_name_idx on products(lower(name));
create index if not exists products_tenant_idx on products(tenant_id);

-- Inventory movements (ledger)
create table if not exists inventory_movements (
  id bigserial primary key,
  product_id bigint not null references products(id) on delete cascade,
  tenant_id bigint null references tenants(id) on delete set null,
  change_qty integer not null,
  reason text not null,
  ref_type text null,
  ref_id bigint null,
  created_by bigint null references users(id) on delete set null,
  created_at timestamptz not null default now()
);

alter table inventory_movements add column if not exists tenant_id bigint references tenants(id) on delete set null;
alter table inventory_movements add column if not exists ref_type text;
alter table inventory_movements add column if not exists ref_id bigint;
alter table inventory_movements add column if not exists created_by bigint references users(id) on delete set null;

create index if not exists inventory_movements_product_idx on inventory_movements(product_id);
create index if not exists inventory_movements_tenant_idx on inventory_movements(tenant_id);
create index if not exists inventory_movements_ref_idx on inventory_movements(ref_type, ref_id);
create index if not exists inventory_movements_created_idx on inventory_movements(created_at desc);

-- ----------------------------------------------------------------------------
-- Orders + payments
-- ----------------------------------------------------------------------------
create table if not exists orders (
  id bigserial primary key,
  tenant_id bigint null references tenants(id) on delete set null,
  status text not null default 'open' check (status in ('open','paid','canceled')),
  subtotal_cents integer not null default 0 check (subtotal_cents >= 0),
  tax_cents integer not null default 0 check (tax_cents >= 0),
  total_cents integer not null default 0 check (total_cents >= 0),
  discount_cents integer not null default 0 check (discount_cents >= 0),
  change_cents integer not null default 0 check (change_cents >= 0),
  amount_received_cents integer not null default 0 check (amount_received_cents >= 0),
  payment_method text not null default 'cash',
  receipt_no text not null default '',
  transaction_id text not null default '',
  cashier_name text null,
  cashier_username text null,
  created_by bigint null references users(id) on delete set null,
  created_at timestamptz not null default now(),
  paid_at timestamptz null
);

alter table orders add column if not exists tenant_id bigint references tenants(id) on delete set null;
alter table orders add column if not exists discount_cents integer not null default 0 check (discount_cents >= 0);
alter table orders add column if not exists change_cents integer not null default 0 check (change_cents >= 0);
alter table orders add column if not exists amount_received_cents integer not null default 0 check (amount_received_cents >= 0);
alter table orders add column if not exists payment_method text not null default 'cash';
alter table orders add column if not exists receipt_no text;
alter table orders add column if not exists transaction_id text;
alter table orders add column if not exists cashier_name text;
alter table orders add column if not exists cashier_username text;

update orders
set receipt_no = coalesce(receipt_no, concat('RCPT-', id)),
    transaction_id = coalesce(transaction_id, concat('TXN-', id))
where receipt_no is null
   or transaction_id is null;

do $$
begin
  begin
    alter table orders alter column receipt_no set not null;
    alter table orders alter column receipt_no set default '';
  exception when others then
    null;
  end;
  begin
    alter table orders alter column transaction_id set not null;
    alter table orders alter column transaction_id set default '';
  exception when others then
    null;
  end;
end
$$;

create index if not exists orders_status_idx on orders(status);
create index if not exists orders_created_at_idx on orders(created_at desc);
create index if not exists orders_tenant_idx on orders(tenant_id);
create unique index if not exists orders_transaction_id_idx on orders(transaction_id);
create index if not exists orders_receipt_no_idx on orders(receipt_no);
create unique index if not exists orders_receipt_no_tenant_idx on orders(tenant_id, receipt_no);
create index if not exists orders_cashier_name_idx on orders(lower(cashier_name));
create index if not exists orders_cashier_username_idx on orders(lower(cashier_username));

do $$
begin
  if exists (select 1 from pg_constraint where conname in ('orders_payment_method_check', 'orders_payment_method_chk')) then
    alter table orders drop constraint if exists orders_payment_method_check;
    alter table orders drop constraint if exists orders_payment_method_chk;
  end if;
exception when others then
  null;
end
$$;

alter table orders add constraint orders_payment_method_chk check (payment_method in ('cash','qr','card','transfer','mobile_money'));

create table if not exists order_items (
  id bigserial primary key,
  order_id bigint not null references orders(id) on delete cascade,
  product_id bigint null references products(id) on delete set null,
  name_snapshot text not null,
  sku_snapshot text not null,
  unit_price_cents integer not null check (unit_price_cents >= 0),
  qty integer not null default 1 check (qty > 0),
  line_total_cents integer not null check (line_total_cents >= 0),
  created_at timestamptz not null default now()
);

create index if not exists order_items_order_id_idx on order_items(order_id);

create table if not exists payments (
  id bigserial primary key,
  order_id bigint not null references orders(id) on delete cascade,
  method text not null default 'cash' check (method in ('cash','card','transfer','mobile_money')),
  amount_cents integer not null check (amount_cents >= 0),
  created_at timestamptz not null default now()
);

do $$
begin
  if exists (select 1 from pg_constraint where conname in ('payments_method_check', 'payments_method_chk')) then
    alter table payments drop constraint if exists payments_method_check;
    alter table payments drop constraint if exists payments_method_chk;
  end if;
exception when others then
  null;
end
$$;

alter table payments add constraint payments_method_chk check (method in ('cash','card','transfer','mobile_money','qr'));

create index if not exists payments_order_id_idx on payments(order_id);

-- ----------------------------------------------------------------------------
-- Data alignment + seeds
-- ----------------------------------------------------------------------------
do $$
declare
  default_tid bigint;
begin
  select id into default_tid from tenants where slug = 'default' limit 1;
  if default_tid is not null then
    update categories set tenant_id = default_tid where tenant_id is null;
    update products set tenant_id = default_tid where tenant_id is null;
    update orders set tenant_id = default_tid where tenant_id is null;
  end if;
end
$$;

-- Seed categories and products per default tenant for demo UX
insert into categories (tenant_id, name, active) values
  ((select id from tenants where slug = 'default' limit 1), 'Beverages', true),
  ((select id from tenants where slug = 'default' limit 1), 'Bakery', true),
  ((select id from tenants where slug = 'default' limit 1), 'Snacks', true),
  ((select id from tenants where slug = 'default' limit 1), 'Essentials', true)
on conflict (tenant_id, name) do update
set active = excluded.active;

insert into products (tenant_id, sku, name, category_id, price_cents, active)
values
  ((select id from tenants where slug = 'default' limit 1), 'COKE-50', 'Coke 50cl', (select id from categories where name = 'Beverages' and tenant_id = (select id from tenants where slug = 'default' limit 1) limit 1), 45000, true),
  ((select id from tenants where slug = 'default' limit 1), 'WATER-50', 'Bottled Water', (select id from categories where name = 'Beverages' and tenant_id = (select id from tenants where slug = 'default' limit 1) limit 1), 25000, true),
  ((select id from tenants where slug = 'default' limit 1), 'BREAD-S', 'Bread (Small)', (select id from categories where name = 'Bakery' and tenant_id = (select id from tenants where slug = 'default' limit 1) limit 1), 70000, true),
  ((select id from tenants where slug = 'default' limit 1), 'SNACK-01', 'Chin-Chin', (select id from categories where name = 'Snacks' and tenant_id = (select id from tenants where slug = 'default' limit 1) limit 1), 60000, true),
  ((select id from tenants where slug = 'default' limit 1), 'MILK-1L', 'Milk 1L', (select id from categories where name = 'Essentials' and tenant_id = (select id from tenants where slug = 'default' limit 1) limit 1), 180000, true),
  ((select id from tenants where slug = 'default' limit 1), 'SUGAR-500', 'Sugar 500g', (select id from categories where name = 'Essentials' and tenant_id = (select id from tenants where slug = 'default' limit 1) limit 1), 90000, true)
on conflict (tenant_id, sku) do update
set name = excluded.name,
    category_id = excluded.category_id,
    price_cents = excluded.price_cents,
    active = excluded.active;
