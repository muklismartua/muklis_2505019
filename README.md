# Tokoku

Aplikasi manajemen stok dan transaksi multi-item berbasis Laravel 12, Blade, Tailwind CSS, Alpine.js, dan SQLite.

## Menjalankan aplikasi

```powershell
composer install
npm install
php artisan migrate --seed
php artisan storage:link
```

Jalankan `npm run dev` dan `php artisan serve` pada dua terminal terpisah.

Seed membuat akun admin `admin@example.com` dengan kata sandi awal `password`, role aplikasi, serta data contoh master data dan produk. Ganti kata sandi awal sebelum aplikasi digunakan.

## Master data

Modul kategori, satuan, supplier, pelanggan, dan produk menggunakan permission dari Spatie Laravel Permission. Produk menyimpan harga sebagai rupiah integer, fotonya berada pada disk `public`, dan penghapusan produk menggunakan soft delete. Stok awal produk dibuat nol dan perubahan stok dilakukan oleh layanan transaksi stok.

Jalankan tes dengan `php artisan test`.
