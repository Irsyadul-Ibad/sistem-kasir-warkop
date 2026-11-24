# AGENT.md - Sistem Kasir & Keuangan Warkop (v1.1)

Panduan untuk AI coding agent dan kontributor. File ini juga bisa dipakai sebagai **daftar aturan** saat kamu mengerjakan proyek secara manual. Detail produk ada di PRD.

## 1. Ringkasan Proyek

Sistem kasir dan keuangan warkop: transaksi, pembayaran (tunai/QRIS/transfer/bon), pengeluaran, laporan laba, pesanan lewat **QR meja**, dan mode offline untuk kasir.

Satu backend, tiga "pintu":

| Pintu | Pengguna | Teknologi | Login |
|---|---|---|---|
| Aplikasi kasir (PWA, HP/tablet/laptop) | Kasir, owner | React + Vite + TypeScript | Wajib |
| Dashboard owner | Owner | Laravel (web) | Wajib |
| Halaman pesan `/m/{token}` | Pelanggan | Halaman publik ringan | **Tidak ada** |

Struktur repo:

```
/
├── web/    # Laravel 11+ : API /api/v1, API publik /api/public, dashboard owner
├── app/    # React PWA : kasir
└── AGENT.md
```

## 2. Perintah Umum

Backend (`web/`):

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan test            # jalankan sebelum commit
./vendor/bin/pint           # format kode
```

Frontend (`app/`):

```bash
npm install
npm run dev
npm run test && npm run lint && npm run typecheck
npm run build
```

## 3. Gerbang Akses: Crew vs Pelanggan (PENTING)

- **Crew** (`owner`, `cashier`) punya akun di tabel `users`, login lewat layar login (username/password atau PIN), memakai token Sanctum.
- **Pelanggan** tidak punya akun. Pintu masuknya QR meja yang berisi link `https://<domain>/m/{token}`.
- Route dipisah: `/api/v1/*` (crew, wajib token Sanctum) dan `/api/public/*` (pelanggan, hanya lewat token meja, rate limit).
- **Token meja tidak boleh bisa dipakai di `/api/v1/*`.** Jangan membuat satu guard untuk keduanya.
- Otorisasi dicek di server (Policy/Gate) pada setiap request. Menyembunyikan tombol di UI saja tidak cukup.
- Token meja acak (mis. 16+ karakter), bukan nomor meja. Owner bisa membuat ulang token; QR lama langsung tidak berlaku.
- Halaman publik hanya boleh: membaca menu aktif, membuat pesanan, melihat status pesanan miliknya (lewat kode pesanan).

Peran crew:

- `cashier`: transaksi, konfirmasi pesanan QR, catat pengeluaran, catat bon dan terima pelunasan. **Tidak boleh**: ubah harga/menu, lihat laporan laba, kelola pengguna dan meja, void tanpa persetujuan owner.
- `owner`: semua fitur, termasuk laporan, menu, pengguna, meja/QR.

## 4. Aturan Domain (WAJIB dipatuhi)

1. **Uang = integer rupiah.** Jangan pakai float/decimal. Format tampilan hanya di UI.
2. **Snapshot harga.** `transaction_items` menyimpan `menu_name_snapshot` dan `price_snapshot`.
3. **Harga selalu dari server.** Total dan harga dihitung ulang dari database; jangan percaya nilai dari klien (aplikasi kasir maupun halaman publik).
4. **Transaksi tidak dihapus permanen.** Hanya di-**void** dengan alasan, dicatat di `audit_logs`. Pakai soft delete untuk data keuangan.
5. **Atomik.** Transaksi + item + utang dalam satu `DB::transaction`.
6. **Idempoten.** Setiap pesanan dari klien membawa `client_uuid` (unique); request ulang mengembalikan data yang sudah ada.
7. **Bon bukan kas masuk.** Pemasukan tercatat saat pelunasan (`debt_payments.paid_at`).
8. **Laba** = pemasukan (penjualan lunas + pelunasan bon) - pengeluaran, pada periode sama.
9. **Pesanan QR** (`channel = 'qr'`) mulai berstatus `menunggu_konfirmasi`. Alur status: `menunggu_konfirmasi -> dibayar -> diproses -> siap -> selesai`; tidak dikonfirmasi dalam 15 menit -> `kedaluwarsa`. Pesanan belum dibayar atau kedaluwarsa **tidak dihitung** sebagai pemasukan.
10. **Tahap 1: bayar di kasir.** Pembayaran QRIS otomatis (payment gateway) adalah tahap 2; jangan dibangun sebelum diminta.
11. **Konsumsi crew** ditandai `is_crew_consumption` dan dipisahkan di laporan. **Kasbon crew** dicatat seperti bon (`debtor_type = 'crew'`).
12. **Zona waktu** `Asia/Jakarta`; simpan UTC, konversi saat menghitung "hari" laporan.
13. **Nomor invoice** `INV-YYYYMMDD-XXXX`, unik per hari.

## 5. Konvensi Backend (Laravel)

- Controller tipis; logika bisnis di `app/Services/` (`TransactionService`, `OrderService`, `ReportService`).
- Validasi lewat `FormRequest`, response lewat `API Resource`.
- Versi API `/api/v1`; jangan ubah kontrak response tanpa menandai breaking change.
- Migrasi kompatibel PostgreSQL **dan** MySQL.
- Hindari N+1 (`with()`); query laporan pakai agregasi DB dan indeks (`transactions.paid_at`, `expenses.expense_date`, `tables.qr_token`).
- Rate limit pada login dan seluruh `/api/public/*` (per IP dan per token).
- Real-time antrean pesanan: Laravel Reverb atau Pusher (event saat pesanan baru dan saat status berubah).
- Ekspor laporan berat lewat queue.

## 6. Konvensi Frontend (React)

- TypeScript strict, hindari `any`. Struktur per fitur: `features/pos`, `orders`, `expenses`, `debts`, `reports`, `tables`.
- Data server: TanStack Query. Keranjang: Zustand. Offline: Dexie (IndexedDB). PWA: vite-plugin-pwa/Workbox.
- Offline-first untuk kasir: transaksi dan pengeluaran masuk antrean lokal lalu disinkronkan; tampilkan status online/offline dan jumlah data belum sinkron.
- Responsif: HP (satu kolom), tablet/laptop (menu di kiri, keranjang di kanan).
- Tombol minimal 44px, bahasa Indonesia, kontras tinggi. Format rupiah lewat satu helper `formatRupiah`.
- Halaman pelanggan `/m/{token}` dibuat ringan (tanpa library berat), tanpa memakai token atau state crew.

## 7. Pengujian

Backend wajib punya test untuk: total/diskon/kembalian, bon dan pelunasan, void, laporan, otorisasi per peran, idempotensi, **token meja tidak bisa mengakses API crew**, kedaluwarsa pesanan QR, dan harga dari klien diabaikan.

Frontend: Vitest (keranjang, format), Playwright (alur kasir tunai/bon, offline, pesan via QR).

Jangan anggap selesai sebelum test, lint, dan typecheck lulus. Bug diperbaiki dengan test yang gagal dulu.

## 8. Keamanan

- Jangan commit `.env`, kunci, atau data pelanggan asli.
- Jangan log password, token, PIN, atau token meja.
- Hash password dan PIN; validasi dan sanitasi semua input di server.
- Upload: validasi tipe dan ukuran, simpan di storage non-eksekusi.
- `audit_logs` untuk: void, edit/hapus pengeluaran, ubah harga, ubah peran, buat ulang token meja.

## 9. Urutan Pengerjaan Manual (disarankan)

Kerjakan berurutan; tiap langkah harus bisa dicoba sebelum lanjut.

1. Siapkan `web/` dan `app/`, database lokal, Git.
2. Migrasi `users`, `categories`, `menus`, seeder owner dan kasir, login Sanctum dengan peran.
3. `transactions` dan `transaction_items`, endpoint buat transaksi (total dari server) + test.
4. Aplikasi: login, daftar menu, keranjang, bayar tunai.
5. Pengeluaran dan kategori.
6. Dashboard owner: ringkasan harian dan bulanan.
7. Bon dan pelunasan; konsumsi dan kasbon crew.
8. Shift dan tutup kas.
9. Meja dan QR: tabel `tables`, halaman `/m/{token}`, konfirmasi kasir, antrean real-time.
10. Offline sync (PWA), ekspor Excel/PDF, backup harian.
11. Uji coba di warkop, baru pertimbangkan tahap 2 (payment gateway).

Tips: commit kecil per langkah, tulis test untuk perhitungan uang sebelum lanjut, dan periksa daftar "Yang Tidak Boleh Dilakukan" di bawah sebelum merge.

## 10. Alur Kerja Agent

1. Baca PRD dan bagian relevan file ini sebelum mulai.
2. Untuk tugas besar, usulkan rencana singkat lalu kerjakan bertahap.
3. Ubah seminimal mungkin; jangan refactor di luar lingkup.
4. Jalankan test, lint, typecheck; laporkan hasilnya apa adanya.
5. Jika aturan bisnis ambigu (cicilan bon, diskon, kedaluwarsa pesanan), **tanyakan**, jangan menebak.
6. Commit kecil dengan Conventional Commits: `feat:`, `fix:`, `test:`, `refactor:`, `docs:`.

## 11. Yang Tidak Boleh Dilakukan

- Memakai float untuk uang.
- Menghapus permanen transaksi atau pengeluaran.
- Menghitung total atau menerima harga dari klien.
- Memakai token meja atau halaman publik untuk mengakses API crew.
- Memakai nomor meja polos sebagai token QR.
- Menghitung pesanan QR yang belum dibayar sebagai pemasukan.
- Mengubah skema tanpa migrasi, atau mengedit migrasi lama yang sudah berjalan di produksi.
- Menambah dependensi besar tanpa alasan.
- Membangun fitur tahap 2 (payment gateway), stok otomatis, multi-cabang sebelum diminta.

## 12. Cakupan

**MVP (tahap 1):** auth + peran, kasir/POS, metode bayar (dicatat), pengeluaran, menu, bon, dashboard laporan, shift, QR meja (bayar di kasir), antrean dapur, konsumsi dan kasbon crew, offline sync, ekspor.

**Tahap 2 dan nanti:** QRIS otomatis (Midtrans/Xendit), stok bahan baku, printer thermal, multi-outlet, notifikasi WhatsApp.
