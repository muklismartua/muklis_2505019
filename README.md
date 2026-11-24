# Tokoku

Aplikasi manajemen stok dan transaksi multi-item berbasis web dengan Laravel 12, PHP 8.2+, SQLite, Blade, Tailwind CSS, Alpine.js, Laravel Breeze, Spatie Permission, DomPDF, dan Laravel Excel.

## Persyaratan

- PHP 8.2 atau lebih baru beserta ekstensi Laravel yang diperlukan, termasuk PDO SQLite
- Composer
- Node.js dan npm

## Instalasi

Jalankan dari folder proyek:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite
```

Pastikan `.env` mengatur SQLite:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Hapus variabel koneksi DB host/port/username/password jika masih ada. Lalu migrasikan basis data, buat akun dan data contoh, serta tautkan penyimpanan foto:

```powershell
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

Jika perlu mengisi ulang database lokal dari awal, jalankan `php artisan migrate:fresh --seed`. Perintah ini menghapus semua data yang tersimpan.

## Menjalankan aplikasi

Buka dua terminal di folder proyek. Jalankan Vite pada terminal pertama:

```powershell
npm run dev
```

Jalankan aplikasi Laravel pada terminal kedua:

```powershell
php artisan serve
```

Buka URL yang ditampilkan Artisan, biasanya `http://127.0.0.1:8000`.

## Akun awal

Semua akun seeder memakai kata sandi awal `password`. Ganti kata sandi sebelum aplikasi dipakai:

| Role | Email |
| --- | --- |
| Admin | `admin@example.com` |
| Staf gudang | `staf_gudang@example.com` |
| Kasir | `kasir@example.com` |
| Owner | `owner@example.com` |

Registrasi publik dinonaktifkan. Admin dapat mengelola pengguna dan role dari menu **Pengguna**.

## Fitur

- Hak akses berbasis role dan permission untuk admin, staf gudang, kasir, dan owner.
- CRUD kategori, satuan, supplier, pelanggan, dan produk, termasuk pencarian, pagination, batas hutang pelanggan, kode pelanggan otomatis, upload foto, serta penghapusan produk secara soft delete. Kasir dapat mencari atau membuat pelanggan langsung dari form penjualan; penghapusan pelanggan khusus admin.
- Kartu stok, stok opname, dan modul stok manual multi-item. Penambahan/pengurangan manual dicatat per item dengan nomor `SM-YYYYMMDD-####`, alasan terpilih, pengguna, tanggal, serta keterangan opsional; semua mutasi dilakukan oleh `StockService`.
- Transaksi pembelian dan penjualan multi-item, validasi stok di server, pembatalan transaksi, dan nomor transaksi harian dengan prefix yang dapat diatur.
- Pembayaran penjualan tempo wajib memilih pelanggan; sisa pembayaran disimpan di tabel piutang dan ditampilkan pada detail transaksi serta dokumen penjualan.
- Pengelolaan piutang per pelanggan dengan jatuh tempo, cicilan/pelunasan bernomor, sinkronisasi status pembayaran penjualan, dan validasi batas hutang pelanggan.
- Invoice penjualan PDF, nota penjualan 80 mm, dan bukti pembelian PDF.
- Dashboard ringkasan, grafik penjualan, laporan penjualan/pembelian/stok/mutasi/laba-rugi dengan ekspor Excel dan PDF.
- Log aktivitas login, perubahan master data, transaksi, dan stok opname; hanya admin yang dapat mengaksesnya.
- Pengaturan informasi toko, logo, prefix transaksi, dan persentase pajak default.
- Antarmuka dan pesan validasi berbahasa Indonesia, dengan halaman kesalahan 403 dan 404 yang ramah.

Nominal disimpan sebagai bilangan bulat rupiah. Zona waktu aplikasi menggunakan `Asia/Jakarta`.

## Pengujian dan pemeriksaan

```powershell
php artisan test
vendor\bin\pint --test
npm run build
```
