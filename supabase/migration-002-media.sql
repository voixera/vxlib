-- ============================================================
-- VoiXLib migration 002 — katalog media eksternal (AniList)
-- Jalankan di Supabase SQL editor SEBELUM deploy versi ini.
-- ============================================================

alter table public.books add column if not exists media_type   text not null default 'text';   -- manga | manhwa | manhua | text
alter table public.books add column if not exists alt_title    text;
alter table public.books add column if not exists banner_url   text;
alter table public.books add column if not exists artist       text;
alter table public.books add column if not exists chapters     int;
alter table public.books add column if not exists volumes      int;
alter table public.books add column if not exists avg_score    int;                            -- 0..100, dari provider
alter table public.books add column if not exists status_label text;                           -- Sedang Berlangsung / Selesai / ...

create index if not exists books_media_idx on public.books (media_type);
