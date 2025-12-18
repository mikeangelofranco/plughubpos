create table if not exists users (
  id bigserial primary key,
  username text not null unique,
  password_hash text not null,
  role text not null default 'User',
  active boolean not null default true,
  created_at timestamptz not null default now()
);

do $$
begin
  if not exists (select 1 from pg_constraint where conname = 'users_role_chk') then
    alter table users add constraint users_role_chk check (role in ('Manager','User'));
  end if;
exception when others then
  -- ignore (e.g., insufficient privileges or older schema state)
  null;
end
$$;

-- Default admin user (Manager role):
--   username: admin
--   password: Cablet0w
insert into users (username, password_hash, role, active)
values ('admin', '$2y$10$VgP/nNQc/.QaisFpgfrJQemVobXWUrjJbnTG.KdJ4hiCdkdTH863O', 'Manager', true)
on conflict (username) do update
set password_hash = excluded.password_hash,
    role = excluded.role,
    active = excluded.active;

-- Seed categories and products for demo UX
insert into categories (name) values
  ('Beverages'),
  ('Bakery'),
  ('Snacks'),
  ('Essentials')
on conflict (name) do nothing;

insert into products (sku, name, category_id, price_cents, active)
values
  ('COKE-50', 'Coke 50cl', (select id from categories where name = 'Beverages'), 45000, true),
  ('WATER-50', 'Bottled Water', (select id from categories where name = 'Beverages'), 25000, true),
  ('BREAD-S', 'Bread (Small)', (select id from categories where name = 'Bakery'), 70000, true),
  ('SNACK-01', 'Chin-Chin', (select id from categories where name = 'Snacks'), 60000, true),
  ('MILK-1L', 'Milk 1L', (select id from categories where name = 'Essentials'), 180000, true),
  ('SUGAR-500', 'Sugar 500g', (select id from categories where name = 'Essentials'), 90000, true)
on conflict (sku) do update
set name = excluded.name,
    category_id = excluded.category_id,
    price_cents = excluded.price_cents,
    active = excluded.active;

create table if not exists categories (
  id bigserial primary key,
  name text not null unique,
  created_at timestamptz not null default now()
);

create table if not exists products (
  id bigserial primary key,
  sku text not null unique,
  name text not null,
  category_id bigint null references categories(id) on delete set null,
  price_cents integer not null check (price_cents >= 0),
  active boolean not null default true,
  created_at timestamptz not null default now()
);

create index if not exists products_active_idx on products(active);
create index if not exists products_name_idx on products(lower(name));

create table if not exists orders (
  id bigserial primary key,
  status text not null default 'open' check (status in ('open','paid','canceled')),
  subtotal_cents integer not null default 0 check (subtotal_cents >= 0),
  tax_cents integer not null default 0 check (tax_cents >= 0),
  total_cents integer not null default 0 check (total_cents >= 0),
  created_by bigint null references users(id) on delete set null,
  created_at timestamptz not null default now(),
  paid_at timestamptz null
);

create index if not exists orders_status_idx on orders(status);
create index if not exists orders_created_at_idx on orders(created_at desc);

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

create index if not exists payments_order_id_idx on payments(order_id);
