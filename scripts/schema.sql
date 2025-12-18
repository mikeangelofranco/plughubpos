create table if not exists users (
  id bigserial primary key,
  username text not null unique,
  password_hash text not null,
  role text not null default 'cashier',
  active boolean not null default true,
  created_at timestamptz not null default now()
);

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

