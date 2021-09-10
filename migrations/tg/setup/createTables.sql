-- @todo: Add slow query logs!

-- do not remove it, it is for tests
create table sample_table (
  id uuid primary key,
  test_field text
);

create table bot_user (
  id uuid,
  first_name text,
  last_name text,
  telegram_id bigint,
  telegram_handle text,

  preferred_gender smallint,
  gender smallint,
  status int,
  registered_at timestamptz,

  is_initiated bool default false,

  seen_qty int default 0,
  last_seen_at timestamptz default now(),
  like_qty int default 0,
  dislike_qty int default 0,

  primary key (telegram_id),
  unique (id)
);

create table viewed_pair (
  recipient_telegram_id bigint,
  pair_telegram_id bigint,
  viewed_at timestamptz,
  reaction smallint,

  primary key (recipient_telegram_id, pair_telegram_id)
);

grant usage, select on all sequences in schema public to tg;
grant select, insert, update on all tables in schema public to tg;
