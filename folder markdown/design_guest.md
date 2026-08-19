# design_guest.md — Desain Landing Page (Guest)

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Halaman:** Homepage / Landing Page (dapat diakses tanpa login)  
**File:** `public/index.php`  
**Warna Utama:** Rose `#c9637a` | Dark `#1a1a2e` | Rose Light `#f8e6ea` | Gold `#c9a96e`  
**Font:** Playfair Display (heading) + Poppins (body)  
**Framework:** Bootstrap 5.3

---

## Ringkasan Halaman

Halaman ini adalah wajah utama Quemil Makeup yang dapat diakses oleh siapa saja tanpa login.
Tujuannya adalah memperkenalkan layanan, menampilkan portofolio karya, dan mendorong pengunjung
untuk melakukan booking.

---

## 1. Navbar

**Posisi:** Sticky top, background putih, shadow ringan (`shadow-sm`)

```
+------------------------------------------------------------------+
| [Q] Quemil Makeup    Portofolio  Layanan  Kontak    [Masuk] [Daftar] |
+------------------------------------------------------------------+
```

**Elemen:**
- Kiri: Logo lingkaran rose berisi huruf "Q" + teks "Quemil **Makeup**" (Makeup berwarna rose)
- Tengah: Link navigasi anchor ke section di halaman (`#portfolio`, `#layanan`, `#kontak`)
- Kanan: Tombol `[Masuk]` outline-rose + tombol `[Daftar]` rose solid
- Mobile: Hamburger menu, semua item collapse ke bawah

**Catatan jika sudah login:**
- Tombol Masuk/Daftar diganti dengan tombol dashboard (user/admin) + tombol Keluar

---

## 2. Hero Section

**Background:** Gradient dari rose-light `#f8e6ea` ke putih (sudut 135deg)  
**Tinggi:** Minimal 90vh  
**Layout:** 2 kolom (6-6 pada lg, full width pada mobile)

```
+-------------------------------------+-----------------------------+
|                                     |                             |
|  [ Makeup Artist Profesional ]      |     ( lingkaran rose )      |
|                                     |    +-------------------+    |
|  Tampil Cantik &                    |    |                   |    |
|  Percaya Diri                       |    |   [FOTO HERO]     |    |
|  di Setiap Momen                    |    |                   |    |
|                                     |    +-------------------+    |
|  Quemil Makeup hadir untuk...       |                             |
|  (subjudul 2 baris)                 |                             |
|                                     |                             |
|  [Booking Sekarang]  [Lihat Porto.] |                             |
|                                     |                             |
|  200+        5+        4.9          |                             |
|  Klien Puas  Thn Pengalaman Rating  |                             |
+-------------------------------------+-----------------------------+
```

**Elemen Kiri:**
- Badge pill rose: "Makeup Artist Profesional" (icon bintang)
- Heading `h1` font Playfair Display, ukuran clamp(2rem, 5vw, 3.5rem)
- "Percaya Diri" berwarna rose
- Subjudul teks abu-abu, max-width 480px
- Tombol CTA primer: `[Booking Sekarang]` rose solid, icon kalender
- Tombol CTA sekunder: `[Lihat Portofolio]` outline-rose, icon images
- Mini stats: 3 angka sejajar (200+ Klien, 5+ Tahun, 4.9 Rating)

**Elemen Kanan:**
- Lingkaran background rose-light sebagai dekorasi
- Foto hero dengan border-radius 16px, box-shadow rose
- Fallback: placeholder `placehold.co` jika foto belum ada
- Hidden pada mobile (`d-none d-lg-block`)

---

## 3. Stats Banner

**Background:** Gradient rose `#c9637a` ke rose-dark `#a84d62`  
**Padding:** 3rem atas-bawah  
**Layout:** 4 kolom sejajar

```
+----------------------------------------------------------------+
|  [rose gradient background]                                    |
|                                                                |
|    200+          5            100%          4.9 ★              |
|  Klien Terlayani  Jenis Layanan  Booking Online  Rating        |
|                                                                |
+----------------------------------------------------------------+
```

**Elemen per kolom:**
- Angka besar font Playfair Display (2.5rem, bold)
- Label kecil opacity 75%
- Warna teks putih semua
- Mobile: 2x2 grid

---

## 4. Section Layanan

**ID:** `#layanan`  
**Background:** Putih  
**Padding:** 5rem atas-bawah  
**Layout:** Grid 3 kolom (sm: 2 kolom, xs: 1 kolom)

```
             Layanan Kami
          [--- rose divider ---]
     Pilih jenis layanan yang sesuai

+------------------+ +------------------+ +------------------+
|    ( icon ★ )    | |    ( icon ★ )    | |    ( icon ★ )    |
|                  | |                  | |                  |
|  Makeup Wisuda   | | Makeup Pengantin | | Makeup Karnaval  |
|                  | |                  | |                  |
| Riasan natural.. | | Riasan lengkap.. | | Riasan kreatif.. |
|                  | |                  | |                  |
|   Rp 150.000     | |   Rp 500.000     | |   Rp 200.000     |
|                  | |                  | |                  |
| [Booking Sekarang| | [Booking Sekarang| | [Booking Sekarang|
+------------------+ +------------------+ +------------------+
```

**Elemen per kartu:**
- Border `1px solid #f0e0e4`, border-radius 12px
- Hover: border berubah rose, shadow rose, naik 4px (`translateY(-4px)`)
- Icon lingkaran rose-light (60x60px) berisi icon Bootstrap Icons
- Nama layanan `h5` semi-bold
- Deskripsi teks abu-abu kecil
- Harga besar berwarna rose (`service-price`)
- Tombol `[Booking Sekarang]` rose solid full-width

---

## 5. Section Portofolio

**ID:** `#portfolio`  
**Background:** Light `#f8f9fa`  
**Padding:** 5rem atas-bawah  
**Layout:** Grid 3 kolom (sm: 2 kolom, xs: 1 kolom)  
**Jumlah foto:** Maksimal 9 foto dari database (aktif + urutan ASC)

```
              Portofolio
          [--- rose divider ---]
      Hasil karya terbaik Quemil Makeup

+------------------+ +------------------+ +------------------+
|                  | |                  | |                  |
|   [FOTO KARYA]   | |   [FOTO KARYA]   | |   [FOTO KARYA]   |
|   260px height   | |   260px height   | |   260px height   |
|                  | |                  | |                  |
| hover: overlay   | | hover: overlay   | | hover: overlay   |
| judul + kategori | | judul + kategori | | judul + kategori |
+------------------+ +------------------+ +------------------+

+------------------+ +------------------+ +------------------+
|   [FOTO KARYA]   | |   [FOTO KARYA]   | |   [FOTO KARYA]   |
+------------------+ +------------------+ +------------------+
```

**Elemen per kartu portofolio:**
- Border-radius 12px, overflow hidden
- Foto: `object-fit: cover`, tinggi tetap 260px
- Hover: foto scale 1.05, overlay gradient gelap muncul dari bawah
- Overlay berisi: judul (putih, semi-bold) + kategori (putih transparan)
- Transition smooth 0.3s ease

**Empty state (jika belum ada foto):**
```
     ( icon bi-images besar )
  Belum ada portofolio tersedia.
```

---

## 6. Section Cara Booking

**Background:** Putih  
**Padding:** 5rem atas-bawah  
**Layout:** 4 kolom horizontal (xs: 2x2)

```
              Cara Booking
          [--- rose divider ---]

  +----------+  +----------+  +----------+  +----------+
  | [01]     |  | [02]     |  | [03]     |  | [04]     |
  |          |  |          |  |          |  |          |
  | (person+)|  |(calendar)|  |(credit   |  |(check    |
  |          |  |          |  | card)    |  | circle)  |
  |          |  |          |  |          |  |          |
  | Daftar / |  |  Pilih   |  | Bayar DP |  |Konfirmasi|
  |  Masuk   |  | Layanan  |  |   30%    |  |  Admin   |
  |          |  |          |  |          |  |          |
  | Buat akun|  |Tentukan  |  |Lakukan   |  |Admin     |
  | atau     |  |jenis,    |  |pembayaran|  |konfirmasi|
  | masuk... |  |tanggal.. |  |DP via    |  |& booking |
  |          |  |          |  |Midtrans  |  |terjadwal |
  +----------+  +----------+  +----------+  +----------+

             [ Booking Sekarang ]
```

**Elemen per langkah:**
- Lingkaran icon rose-light (72x72px) berisi Bootstrap Icon
- Badge nomor urut (01-04) rose, posisi absolute pojok kanan atas lingkaran
- Judul langkah `h6` semi-bold
- Deskripsi singkat teks abu-abu kecil
- Tombol CTA besar di bawah: `[Booking Sekarang]` rose solid

---

## 7. Section Testimoni

**Background:** Light `#f8f9fa`  
**Padding:** 5rem atas-bawah  
**Layout:** 3 kolom (xs: 1 kolom)

```
            Testimoni Pelanggan
          [--- rose divider ---]

+--------------------+ +--------------------+ +--------------------+
| [rose-light bg]    | | [rose-light bg]    | | [rose-light bg]    |
|                    | |                    | |                    |
| ★ ★ ★ ★ ★          | | ★ ★ ★ ★ ★          | | ★ ★ ★ ★ ★          |
|                    | |                    | |                    |
| "Hasilnya luar     | | "Sangat            | | "Riasan            |
|  biasa! Riasannya  | |  profesional dan   | |  karnavalnya       |
|  natural tapi      | |  on-time. Harganya | |  kreatif banget!   |
|  tetap glowing.." | |  juga terjangkau" | |  Tahan lama..."   |
|                    | |                    | |                    |
| [S] Siti Rahayu    | | [D] Dewi Kartika   | | [R] Rina Wulandari |
|     Wisuda S1      | |     Pernikahan     | |     Karnaval       |
+--------------------+ +--------------------+ +--------------------+
```

**Elemen per kartu:**
- Background rose-light, border-radius 16px, padding 1.75rem
- Bintang emas (`#c9a96e`) 5 buah
- Teks kutipan italic, warna abu-abu
- Avatar lingkaran rose berisi inisial nama (40x40px)
- Nama pelanggan + jenis acara

---

## 8. Footer

**ID:** `#kontak`  
**Background:** Dark `#1a1a2e`  
**Teks:** Putih / abu-abu  
**Layout:** 3 kolom + baris copyright

```
+------------------------------------------------------------------+
| [dark background]                                                |
|                                                                  |
|  Quemil Makeup    |  Lokasi Studio      |  Hubungi Kami         |
|                   |                     |                       |
|  Jasa makeup      |  [icon] Dusun Sawi  |  [WA] WhatsApp        |
|  artist           |  RT 08 RW 02,       |  [clock] Senin-Minggu |
|  profesional...   |  Desa Sawiji,       |  06:00-17:00 WIB      |
|                   |  Kec. Jogoroto,     |                       |
|                   |  Kab. Jombang       |                       |
|                   |  Jawa Timur         |                       |
|                                                                  |
|  [--- garis pemisah abu-abu ---]                                 |
|                                                                  |
|         © 2026 Quemil Makeup. Sistem Informasi Booking.          |
+------------------------------------------------------------------+
```

**Elemen:**
- Kolom 1: Logo + deskripsi singkat (teks muted)
- Kolom 2: Alamat lengkap studio dengan icon lokasi rose
- Kolom 3: Link WhatsApp (ikon hijau) + jam operasional
- Copyright di tengah, teks kecil muted

---

## 9. WhatsApp Floating Button

**Posisi:** Fixed, pojok kanan bawah (bottom: 24px, right: 24px)  
**Tampil di:** Semua halaman

```
                              +-------+
                              |  WA   |  <- lingkaran hijau
                              | icon  |     56x56px
                              +-------+
                       (bottom-right corner)
```

**Spesifikasi:**
- Background hijau WhatsApp `#25d366`
- Icon `bi-whatsapp` putih, font-size 1.6rem
- Box shadow hijau `rgba(37,211,102,.4)`
- Hover: scale 1.1
- Link ke `https://wa.me/{nomor}?text=Halo+Quemil+Makeup...`
- z-index: 1000

---

## 10. Responsivitas

| Breakpoint | Perubahan |
|------------|-----------|
| `< 992px` (lg) | Hero image disembunyikan, hero teks full-width |
| `< 768px` (md) | Grid layanan & portofolio jadi 2 kolom |
| `< 576px` (sm) | Grid layanan & portofolio jadi 1 kolom, cara booking 2x2 |
| Semua ukuran | Navbar collapse ke hamburger menu |

---

## 11. Referensi Warna & Class CSS

| Elemen | Warna / Class |
|--------|---------------|
| Tombol utama | `.btn-rose` → `#c9637a` |
| Tombol outline | `.btn-outline-rose` |
| Teks rose | `.text-rose` → `#c9637a` |
| Background rose muda | `rose-light` → `#f8e6ea` |
| Background stats banner | gradient `#c9637a` → `#a84d62` |
| Heading font | `font-family: var(--font-serif)` = Playfair Display |
| Body font | `font-family: var(--font-sans)` = Poppins |
| Bintang testimoni | `.testimonial-stars` → `#c9a96e` (gold) |
| Hover kartu portofolio | shadow `rgba(201,99,122,.2)`, translateY(-6px) |
