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

  status int,
  preferred_gender smallint,
  gender smallint,

  primary key (id),
  unique (telegram_id)
);

grant usage, select on all sequences in schema public to tg;
grant select, insert, update on all tables in schema public to tg;
