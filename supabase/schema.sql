-- ============================================================
-- VoiXLib — Supabase schema
-- Run this once in the Supabase SQL editor (Dashboard → SQL).
-- ============================================================

-- ── Users (Discord identities) ──────────────────────────────
create table if not exists public.users (
  id           bigint generated always as identity primary key,
  discord_id   text        not null unique,
  username     text        not null,
  display_name text,
  avatar_url   text,
  email        text,
  prefs        jsonb       not null default '{}'::jsonb,
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now()
);

-- ── Books catalog ───────────────────────────────────────────
create table if not exists public.books (
  id               bigint generated always as identity primary key,
  external_id      text        not null unique,          -- e.g. gutenberg:1342
  source           text        not null default 'gutenberg',
  gutenberg_id     int,
  title            text        not null,
  author           text        not null,
  author_life      text,
  description      text,
  cover_url        text,                                 -- null → VoiXLib generates an SVG cover
  source_url       text        not null,
  read_url         text,
  language         text        not null default 'en',
  publication_year int,
  page_count       int,
  isbn             text,
  downloads        bigint      not null default 0,       -- popularity signal from the source
  featured         boolean     not null default false,
  subjects         text        not null default '',
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);
create index if not exists books_title_idx    on public.books (title);
create index if not exists books_author_idx   on public.books (author);
create index if not exists books_lang_idx     on public.books (language);
create index if not exists books_year_idx     on public.books (publication_year);
create index if not exists books_popular_idx  on public.books (downloads desc);
create index if not exists books_featured_idx on public.books (featured) where featured;

-- ── Categories ──────────────────────────────────────────────
create table if not exists public.categories (
  id   bigint generated always as identity primary key,
  name text not null unique,
  slug text not null unique
);

create table if not exists public.book_categories (
  book_id     bigint not null references public.books(id)      on delete cascade,
  category_id bigint not null references public.categories(id) on delete cascade,
  primary key (book_id, category_id)
);
create index if not exists bc_category_idx on public.book_categories (category_id);

insert into public.categories (name, slug) values
  ('Classics',            'classics'),
  ('Mystery & Detective', 'mystery'),
  ('Science Fiction',     'science-fiction'),
  ('Fantasy',             'fantasy'),
  ('Romance',             'romance'),
  ('Gothic & Horror',     'gothic-horror'),
  ('Adventure',           'adventure'),
  ('Philosophy',          'philosophy'),
  ('History',             'history'),
  ('Short Stories',       'short-stories'),
  ('Poetry',              'poetry'),
  ('Nature & Science',    'nature-science')
on conflict (slug) do nothing;

-- ── User library shelves ────────────────────────────────────
create table if not exists public.user_library (
  id         bigint generated always as identity primary key,
  user_id    bigint not null references public.users(id)  on delete cascade,
  book_id    bigint not null references public.books(id)  on delete cascade,
  status     text   not null check (status in ('want_to_read','reading','completed')),
  created_at timestamptz not null default now(),
  unique (user_id, book_id)
);
create index if not exists ul_user_idx   on public.user_library (user_id);
create index if not exists ul_status_idx on public.user_library (user_id, status);

-- ── Bookmarks ───────────────────────────────────────────────
create table if not exists public.bookmarks (
  id         bigint generated always as identity primary key,
  user_id    bigint not null references public.users(id) on delete cascade,
  book_id    bigint not null references public.books(id) on delete cascade,
  location   text   not null,                            -- e.g. ch3:1420
  label      text,
  created_at timestamptz not null default now()
);
create index if not exists bm_user_idx on public.bookmarks (user_id);
create index if not exists bm_book_idx on public.bookmarks (book_id);

-- ── Reading progress (one row per user+book) ────────────────
create table if not exists public.reading_progress (
  id         bigint generated always as identity primary key,
  user_id    bigint not null references public.users(id) on delete cascade,
  book_id    bigint not null references public.books(id) on delete cascade,
  progress   int    not null default 0 check (progress between 0 and 100),
  chapter    int    not null default 0,
  location   text,
  updated_at timestamptz not null default now(),
  unique (user_id, book_id)
);
create index if not exists rp_user_idx on public.reading_progress (user_id, updated_at desc);

-- ── Reading history ─────────────────────────────────────────
create table if not exists public.reading_history (
  id             bigint generated always as identity primary key,
  user_id        bigint not null references public.users(id) on delete cascade,
  book_id        bigint not null references public.books(id) on delete cascade,
  last_opened_at timestamptz not null default now(),
  unique (user_id, book_id)
);
create index if not exists rh_user_idx on public.reading_history (user_id, last_opened_at desc);

-- ============================================================
-- Row Level Security
-- The PHP backend uses the service-role key server-side and enforces
-- authorization itself. RLS below is defense-in-depth: even if the anon
-- key leaks into a browser context, private rows stay private.
-- ============================================================

alter table public.users            enable row level security;
alter table public.books            enable row level security;
alter table public.categories       enable row level security;
alter table public.book_categories  enable row level security;
alter table public.user_library     enable row level security;
alter table public.bookmarks        enable row level security;
alter table public.reading_progress enable row level security;
alter table public.reading_history  enable row level security;

-- Public catalog: readable by anyone (anon key included), writable by nobody.
drop policy if exists books_public_read on public.books;
create policy books_public_read on public.books for select using (true);

drop policy if exists categories_public_read on public.categories;
create policy categories_public_read on public.categories for select using (true);

drop policy if exists bc_public_read on public.book_categories;
create policy bc_public_read on public.book_categories for select using (true);

-- Private tables: no anon policies at all. With RLS enabled and no policy,
-- every access is denied unless the service-role key (bypasses RLS) is used.

-- Optional hardening when Supabase Auth JWTs are introduced later:
-- create policy own_rows_select on public.user_library for select
--   using (auth.jwt() ->> 'sub' is not null);  -- adjust to your auth claim mapping

-- Keep updated_at honest where the API writes it directly.
create or replace function public.touch_updated_at() returns trigger as $$
begin new.updated_at = now(); return new; end;
$$ language plpgsql;

drop trigger if exists users_touch on public.users;
create trigger users_touch before update on public.users
  for each row execute function public.touch_updated_at();

drop trigger if exists books_touch on public.books;
create trigger books_touch before update on public.books
  for each row execute function public.touch_updated_at();
