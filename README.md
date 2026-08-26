# VoiXLib

**Perpustakaanmu, melampaui rak.**

Platform discovery + perpustakaan pribadi untuk **Anime, Manga, Manhwa** — metadata nyata dari
[AniList](https://anilist.co), UI Bahasa Indonesia, akun Discord, data pengguna di Supabase.
Backend PHP 8.2 murni (tanpa Composer), frontend vanilla JS/SVG. Tanpa framework.

---

## Prinsip

- **No-dummy:** setiap judul/sinopsis/skor berasal dari AniList. Jika provider tidak menyediakan
  sebuah field, VoiXLib menampilkan "Tidak tersedia" — tidak pernah mengarangnya.
- **Discovery, bukan pembajakan:** VoiXLib tidak menghosting chapter. Halaman detail mengarahkan ke
  sumber resmi ("Lihat Sumber").
- **Service layer per provider:** `AnimeService` / `MangaService` / `ManhwaService` men-facade
  `AniListService` — ganti provider dengan menyentuh satu class saja.

## Setup

### 1. Kebutuhan

- PHP 8.2+ dengan ekstensi `curl`, `mbstring`, `openssl`
- Proyek Supabase (free tier cukup) + aplikasi Discord

### 2. Konfigurasi

```bash
cp .env.example .env   # isi semua nilai, lihat komentar di dalamnya
```

### 3. Skema database

Jalankan di SQL editor Supabase:

1. `supabase/schema.sql` (sekali, untuk deployment baru)
2. `supabase/migration-002-media.sql` (kolom katalog media: media_type, alt_title,
   banner_url, artist, chapters, volumes, episodes, avg_score, status_label)

RLS aktif; tabel milik pengguna tanpa policy publik — akses hanya lewat service-role di backend.

### 4. Jalankan

```bash
php -S localhost:8000 api/index.php
```

Katalog TIDAK di-seed: baris lokal dibuat otomatis (`CatalogRepository::ensureLocal`) saat judul
pertama kali dibuka/disimpan, supaya perpustakaan & bookmark punya foreign key valid.

## Arsitektur

```
/api             front controller serverless (api/index.php)
/routes          entry point halaman (browse, detail, auth/, api/)
/anime /manga /manhwa        rak tipe (rewrite → routes/browse.php)
/detail/{t}/{id}             halaman detail media
/app/Services     AniListService (GraphQL+cache), Anime/Manga/ManhwaService,
                  MediaNormalizer, Auth, Prefs, Http, RateLimiter…
/app/Repositories CatalogRepository (mirror lokal), Library/UserRepository
/resources/views  layout, halaman, komponen (kartu, shelf, state, ikon SVG)
/public/assets    css/js statis
/supabase         schema.sql + migration-002-media.sql
```

## Catatan

- Cache respons provider di storage sementara per-instance (serverless-safe).
- Rate limit pada API publik dan permintaan mutasi.
- `cover.php` menghasilkan sampul fallback SVG elegan (judul + tipe) bila cover kosong/gagal.
- OAuth Discord server-side dengan state bertanda tangan; secret tidak pernah keluar dari `.env`.
