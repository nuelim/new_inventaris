# Sistem Inventaris Aset Laravel 10

Aplikasi Inventaris Aset berbasis web yang dibangun dengan Laravel 10 untuk mengelola aset perusahaan, jadwal maintenance, dan kalibrasi secara efisien.

## 🚀 Fitur Utama

### 📊 Dashboard Komprehensif
- **KPI Metrics**: Total aset, nilai aset, maintenance terlambat, kalibrasi terlambat
- **Interactive Charts**: Distribusi status aset, trend maintenance, analisis lokasi
- **Real-time Updates**: Aktivitas terkini dan peringatan otomatis
- **Filter Options**: Berdasarkan lokasi, tipe aset, dan rentang tanggal

### 📦 Manajemen Aset
- **Data Aset Lengkap**: SKU, nama, deskripsi, spesifikasi, kondisi, status
- **Tracking Lokasi**: Pemindahan aset antar lokasi dengan history
- **QR Code & Barcode**: Generate otomatis untuk identifikasi fisik
- **Warranty Management**: Monitoring garansi dan peringatan kadaluarsa
- **Value Tracking**: Nilai pembelian, nilai saat ini, dan depresiasi

### 🔧 Manajemen Maintenance
- **Jadwal Maintenance**: Rutin, korektif, preventif, darurat
- **Task Assignment**: Penugasan ke teknisi tersedia
- **Progress Tracking**: Dari terjadwal hingga selesai
- **Cost Tracking**: Biaya maintenance per jadwal
- **Recurring Schedules**: Otomatisasi jadwal berulang

### ⚖️ Manajemen Kalibrasi
- **Scheduling System**: Berbagai frekuensi kalibrasi
- **Certificate Management**: Nomor dan masa berl sertifikat
- **Vendor Tracking**: Informasi vendor kalibrasi
- **Expiry Alerts**: Peringatan sertifikat yang akan kadaluarsa

### 🗂️ Master Data
- **Tipe Aset**: Kategori, depresiasi, persyaratan kalibrasi/maintenance
- **Lokasi**: Gedung, lantai, ruang, detail kontak manager
- **Supplier**: Informasi vendor lengkap dengan riwayat pembelian
- **Import/Export**: Bulk data import/export untuk master data

### 📈 Laporan & Analitik
- **Asset Reports**: Daftar aset lengkap dengan filter
- **Maintenance Reports**: History dan statistik maintenance
- **Calibration Reports**: Status kalibrasi dan sertifikat
- **Value Reports**: Analisis nilai aset dan depresiasi
- **Export Options**: PDF dan Excel format

## 🛠️ Teknologi

- **Backend**: Laravel 10
- **Frontend**: Bootstrap 5, Blade Templates
- **Database**: MySQL/PostgreSQL (migrations included)
- **Charts**: Chart.js
- **Authentication**: Laravel Sanctum
- **File Storage**: Local Storage (dapat diintegrasikan dengan cloud storage)

## 📋 Prerequisites

- PHP >= 8.1
- Composer
- Database (MySQL/PostgreSQL/SQLite)
- Web Server (Apache/Nginx)

## 🚀 Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/nuelim/new_inventaris.git
   cd new_inventaris
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Configuration**
   - Edit `.env` file untuk database connection
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=inventaris_aset
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed Database (Optional)**
   ```bash
   php artisan db:seed
   ```

7. **Link Storage**
   ```bash
   php artisan storage:link
   ```

8. **Build Assets**
   ```bash
   npm run build
   ```

9. **Start Development Server**
   ```bash
   php artisan serve
   ```

## 👥 User Management

Aplikasi mendukung sistem role-based access control:

- **Admin**: Akses penuh ke semua fitur
- **Manager**: Manajemen aset dan laporan
- **User**: View dan update terbatas

## 📱 Screenshots

### Dashboard
- Overview KPI dan charts real-time
- Quick actions untuk akses cepat
- Peringatan maintenance dan kalibrasi

### Asset Management
- List view dengan search dan filter
- Detail view dengan history lengkap
- QR code generation untuk tracking

### Scheduling
- Calendar view untuk maintenance dan kalibrasi
- Task assignment ke teknisi
- Progress tracking dan completion

## 🔧 Konfigurasi

### QR Code & Barcode
- QR Code dan barcode generate otomatis saat asset creation
- Storage di `storage/app/public/qrcodes` dan `storage/app/public/barcodes`
- Format: QR-AST-2024-0001, BAR-AST-2024-0001

### Email Notifications
- Konfigurasi email di `.env`
- Automatic reminders untuk maintenance dan kalibrasi
- Notifikasi overdue tasks

### Backup & Recovery
- Database backup terjadwal
- Soft deletes untuk semua data penting
- Audit logging untuk tracking perubahan

## 📄 API Documentation

API endpoints tersedia untuk integrasi dengan sistem lain:
- RESTful API design
- Authentication via Sanctum tokens
- Comprehensive documentation

## 🤝 Kontribusi

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License

Project ini dilisensikan under MIT License - lihat [LICENSE](LICENSE) file untuk details.

## 📞 Support

Untuk support dan pertanyaan:
- Email: support@example.com
- Issues: [GitHub Issues](https://github.com/nuelim/new_inventaris/issues)
- Documentation: [Wiki](https://github.com/nuelim/new_inventaris/wiki)

## 🗺️ Roadmap

- [ ] Mobile App (React Native)
- [ ] Advanced Analytics Dashboard
- [ ] Integration dengan ERP Systems
- [ ] Asset IoT Integration
- [ ] Predictive Maintenance
- [ ] Multi-tenant Support
- [ ] Advanced Reporting

## ⭐ Credits

- [Laravel Framework](https://laravel.com/)
- [Bootstrap](https://getbootstrap.com/)
- [Chart.js](https://www.chartjs.org/)
- [Font Awesome](https://fontawesome.com/)

---

**Inventaris Aset Laravel 10** - Solusi lengkap untuk manajemen aset perusahaan 🚀