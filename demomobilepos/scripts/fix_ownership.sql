do $$
declare r record;
begin
  -- Tables
  for r in
    select schemaname, tablename
    from pg_tables
    where schemaname = 'public'
  loop
    execute format('alter table %I.%I owner to plughub', r.schemaname, r.tablename);
  end loop;

  -- Sequences (bigserial)
  for r in
    select sequence_schema, sequence_name
    from information_schema.sequences
    where sequence_schema = 'public'
  loop
    execute format('alter sequence %I.%I owner to plughub', r.sequence_schema, r.sequence_name);
  end loop;
end $$;

