# Design System — Aplikasi Approval Prestasi

Acuan UI Approval Prestasi, Katalog Prestasi, dan Kelola Admin.

Layout: `resources/views/layouts/approval.blade.php`.

## Prinsip

- Tampilan kantor madrasah, bukan dashboard SaaS ungu.
- Compact: baris rapat, filter menyatu dengan tabel.
- Satu kartu data: filter + toolbar + tabel dalam `.table-card`.
- Aksen hijau (identitas sekolah), kertas hangat, sidebar hutan gelap.
- Notifikasi lewat Toastify yang ringkas, bukan banner.

## Tema

Tombol matahari/bulan di topbar. Pilihan disimpan di `localStorage` (`approval-theme`).

| Token | Terang | Gelap |
|---|---|---|
| `--bg` | `#f2efe6` | `#121512` |
| `--surface` | `#fbfaf4` | `#1b1f1b` |
| `--border` | `#d5cfc0` | `#323932` |
| `--t1` | `#1c2118` | `#e7ebe3` |
| `--t2` | `#5d6458` | `#a3aa9c` |
| `--accent` | `#1f6b3a` | `#6fbf86` |
| Sidebar | `#18241c` | `#0e130f` |

Font: **Source Sans 3** (cadangan Segoe UI). Ikon: Font Awesome 6. Sudut **6px**, bukan kapsul besar.

## Shell

- Sidebar 248px, item `.sb-link`, aktif = blok hijau solid (tanpa gradient).
- Topbar 52px: judul, tombol tema, cakupan sekolah.
- Konten `.body` padding `16px 18px`.

Halaman baru `@extends('layouts.approval')` dan set `$navActive` (`approval`, `katalog`, `admin`).

## Kartu tabel

```
.panel.table-card
  .filters
  .table-toolbar
  .table-wrap
    table.data
```

- Input/tombol filter: **34px**.
- Sel padding **8×11**. Header uppercase kecil, background `--surface-2`.
- Hover baris: `--row-hover`. Tanpa zebra, tanpa bayangan kartu yang dalam.

### Group by

- Baris `.group-row`, colspan penuh, bisa collapse.
- Superadmin: group by sekolah. Role scoped: tabel datar.

### Sel data

- Orang: `.cell-user` + `.avatar` 28px (inisial) + `.cell-main` / `.cell-sub`.
- Angka/tanggal: `.mono`.
- Status: `.badge` (`.pending` `.approved` `.role-sa` `.role-ms`).
- Aksi: `.actions` + `.btn-sm`.

## Tombol

| Kelas | Fungsi |
|---|---|
| `.btn-primary` | Aksi utama |
| `.btn-ghost` | Reset, Ubah, Batal |
| `.btn-ok` | Setujui |
| `.btn-danger` | Tolak / Hapus |
| `.btn-sm` | Di tabel, tinggi 28px |

Tanpa translate/hover “melayang”.

## Tab & filter

- Status: garis bawah (`.tabs` + `.chip.active`), bukan pil.
- Label filter biasa, bukan uppercase lebar.
- Live filter di browser. Tab status tetap server-side.
- Periode: **Dari** dan **Sampai**.

## Katalog (superadmin)

Hierarki di satu kartu: kategori → tingkat → capaian + poin.

- Poin diisi superadmin; nilai awal 0.
- Hapus dari bawah (capaian dulu, lalu tingkat, lalu kategori) jika masih ada anak atau sudah dipakai `aka_reward`.

## Notifikasi

`showToast(message, 'success'|'error')`.

- Kartu tipis: ikon + teks, tanpa judul “Berhasil/Gagal”, tanpa progress bar.
- Konfirmasi: modal `#confirmModal` + `form.js-confirm`.

## Jangan

- Ungu, gradient sidebar, Inter sebagai font utama, pill berlebihan.
- Banner flash atau SweetAlert di modul approval.
- Beberapa panel terpisah hanya untuk filter dan tabel.
- Toast dengan heading + animasi bar.
