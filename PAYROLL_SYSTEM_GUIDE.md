# Sistem Payroll Otomatis - Panduan Penggunaan

## Overview
Sistem payroll otomatis yang terintegrasi dengan data attendance dan overtime. Sistem ini dapat menjalankan perhitungan payroll secara otomatis setiap bulan dan menyediakan interface admin yang canggih untuk pengelolaan manual dengan fitur auto-calculate.

## ✨ Fitur Auto-Calculate Terbaru

### 🤖 Tombol "Calculate from Attendance"
- **Otomatis hitung** semua data dari table attendances & overtimes
- **Real-time preview** sebelum save
- **Smart validation** dengan error handling

### 📅 Period Selector
- **Pilih periode** payroll (monthly/custom range)
- **Auto-fill** based on attendance data
- **Live calculation** saat periode berubah

### 💰 Smart Income Calculation
- **Ambil dari employee_income** di table employees
- **Kalkulasi overtime** berdasarkan approved overtime hours
- **Perhitungan pro-rata** untuk hari kerja

### 🎯 Enhanced Bulk Generation
- **Generate payroll** untuk multiple employees sekaligus
- **Berdasarkan department** atau position
- **Recalculate selected** payrolls dari attendance data

## Fitur Utama

### 1. Perhitungan Otomatis
- **Work Days**: Berdasarkan attendance records dengan status 'Hadir' atau 'Terlambat'
- **Leave Days**: Berdasarkan records dengan `is_leave = true`
- **Permission Days**: Berdasarkan `leave_type = 'permission'`
- **Absent Days**: **SMART CALCULATION** - Menghitung berdasarkan:
  - Jadwal shift employee (workhour dari shift)
  - Membandingkan scheduled workdays vs actual attendance
  - Tidak menghitung hari libur/weekend yang tidak dijadwalkan
  - Tidak menghitung cuti sebagai absent (karena sudah approved)
- **Overtime Hours**: Berdasarkan data overtime yang approved dengan `duration_hour`
- **Pro-rata Salary**: Otomatis sesuai dengan hari kerja aktual
- **Integrasi Shift System**: Mendukung employee, position, dan department level shifts

### 2. Automation
- **Monthly Job**: Generasi payroll otomatis setiap tanggal 1
- **Background Processing**: Menggunakan Laravel Queue
- **Notifikasi**: Email dan database notification untuk admin

### 3. Enhanced Admin Interface (Filament)
- **🤖 Auto Calculate**: Tombol otomatis hitung dari attendance
- **📊 Real-time Preview**: Preview kalkulasi sebelum save
- **🎯 Bulk Actions**: Generate payroll untuk multiple employees
- **🔄 Recalculate**: Update payroll dari data attendance terbaru
- **Manual Override**: Kemampuan edit manual jika diperlukan

### 4. 📊 Dashboard Widgets
- **Monthly Payroll Summary**: Overview biaya payroll bulanan
- **Attendance vs Payroll**: Perbandingan coverage attendance dan payroll
- **Overtime Cost Tracking**: Tracking biaya lembur per bulan
- **Work Hours Analysis**: Analisis jam kerja actual vs expected

## 🔍 Dry-Run Feature

### What is Dry-Run?
Dry-run adalah fitur untuk **preview kalkulasi payroll** tanpa membuat record di database. Berguna untuk:
- ✅ **Validasi data** sebelum generate actual payroll
- ✅ **Cek perhitungan** apakah sudah benar
- ✅ **Estimasi biaya** total payroll
- ✅ **Debug issues** tanpa corrupt data

### Dry-Run Output Example:
```bash
🔍 DRY RUN - No records will be created
================================================
📅 Period: July 2025 (2025-07-01 to 2025-07-31)
👥 Found 5 employees to process:

🧑‍💼 John Doe (1)
   Status: ✅ WOULD CREATE
   Work Days: 22 | Leave: 0 | Absent: 0
   Overtime Hours: 8
   Final Salary: Rp 5,500,000

🧑‍💼 Jane Smith (2)
   Status: ⚠️  ALREADY EXISTS
   Work Days: 20 | Leave: 2 | Absent: 0
   Overtime Hours: 4
   Final Salary: Rp 5,200,000

================================================
📊 SUMMARY:
   Total Employees: 5
   Successfully Calculated: 5
   Total Salary Cost: Rp 26,500,000
================================================
💡 Use without --dry-run to actually create the payroll records.
```

### Cara Penggunaan

### 1. 🤖 Auto-Calculate dari Attendance

#### Via Form Creation/Edit:
1. Pilih **Employee** dan **Period**
2. Klik tombol **🤖 Calculate from Attendance**
3. Sistem akan otomatis mengisi semua field berdasarkan data attendance
4. Review data dan simpan

#### Via Header Action:
1. Di halaman Payrolls, klik **Auto Calculate from Attendance**
2. Pilih employee dan periode
3. Lihat **real-time preview** kalkulasi
4. Centang **Create Payroll Record** jika ingin langsung buat record
5. Klik **Submit**

### 2. 🎯 Bulk Operations

#### Recalculate Selected:
1. Pilih multiple payroll records
2. Gunakan bulk action **Recalculate from Attendance**
3. Konfirmasi untuk memproses
4. Sistem akan update semua payroll berdasarkan data attendance terbaru

#### Generate Bulk Payroll:
1. Pilih mode generation: Department, Position, atau All Active
2. Pilih kriteria (department/position jika applicable)
3. Tentukan periode
4. Pilih background processing atau langsung
5. Klik **Generate**

### 1. Setup Queue Worker
```bash
# Jalankan queue worker untuk background jobs
php artisan queue:work
```

### 2. Manual Generation via Command
```bash
# Generate payroll bulan ini untuk semua employee
php artisan payroll:generate

# Generate untuk bulan/tahun tertentu
php artisan payroll:generate --month=12 --year=2024

# Dry run (preview kalkulasi tanpa membuat record)
php artisan payroll:generate --dry-run

# Dry run untuk periode tertentu
php artisan payroll:generate --month=7 --year=2025 --dry-run

# Generate di background (queue)
php artisan payroll:generate --queue

# Generate synchronous (langsung)
php artisan payroll:generate --sync

# Generate untuk department tertentu
php artisan payroll:generate --department=1 --dry-run

# Generate untuk position tertentu
php artisan payroll:generate --position=2 --dry-run

# Generate untuk employee tertentu
php artisan payroll:generate --employees=1,2,3 --dry-run

# Force overwrite existing payroll
php artisan payroll:generate --force
```

### 3. Via Admin Panel
1. Masuk ke menu **Payrolls**
2. Klik tombol **Generate Payroll** di header
3. Pilih periode dan karyawan
4. Sistem akan menjalankan perhitungan otomatis

### 4. Bulk Generation
1. Di halaman Payrolls, pilih multiple records
2. Gunakan bulk action **Generate Selected Payroll**
3. Konfirmasi untuk memproses

## Perhitungan Payroll

### Formula Dasar
```
Gaji Bersih = Gaji Pokok + Tunjangan + Lembur - Potongan
```

### Detail Perhitungan
- **Gaji Pokok**: Dari employee.basic_salary
- **Tunjangan**: Dari employee.income (JSONB) - transport, meal, etc.
- **Lembur**: overtime_hours × overtime_rate
- **Potongan**: Dari employee.expenses (JSONB) + absent_deduction + late_deduction

### Data Dependencies
- **Employees**: Basic salary, income, expenses
- **Attendances**: Untuk hari kerja, keterlambatan, dan workhours
  - `clock_in_status`: Status clock in ('Hadir', 'Terlambat', 'Tidak Hadir', dll)
  - `clock_out_status`: Status clock out
  - `workhours`: Total jam kerja per hari (float)
  - `is_leave`: Boolean untuk cuti
  - `leave_type`: Jenis cuti (permission, dll)
  - `date`, `clock_in`, `clock_out`: Waktu kehadiran
- **Overtimes**: Untuk perhitungan lembur
  - `duration_hour`: Durasi lembur dalam jam (integer)
  - `status`: Status approval ('approved', dll)
  - `date`, `start_time`, `end_time`: Waktu lembur
- **Holidays**: Untuk menentukan hari kerja efektif

## � Database Structure Reference

### Attendances Table
| Column | Type | Purpose | Values |
|--------|------|---------|---------|
| `employee_id` | FK | Employee reference | - |
| `date` | date | Tanggal attendance | - |
| `clock_in_status` | string | Status clock in | 'Hadir', 'Terlambat', 'Tidak Hadir', 'Cuti' |
| `clock_out_status` | string | Status clock out | Similar to clock_in |
| `workhours` | float | Jam kerja actual | 8.0, 7.5, dll |
| `is_leave` | boolean | Apakah cuti | true/false |
| `leave_type` | string | Jenis cuti | 'permission', 'sick', dll |

### Overtimes Table
| Column | Type | Purpose | Values |
|--------|------|---------|---------|
| `employee_id` | FK | Employee reference | - |
| `duration_hour` | integer | Durasi lembur | 2, 3, 4 (jam) |
| `status` | string | Status approval | 'approved', 'pending', 'rejected' |
| `date` | date | Tanggal lembur | - |

### Payrolls Table
| Column | Type | Purpose | Values |
|--------|------|---------|---------|
| `employee_id` | FK | Employee reference | - |
| `work_days` | integer | Hari kerja | 22, 20, dll |
| `overtime_hours` | integer | Total jam lembur | Sum dari overtimes |
| `employee_income` | jsonb | Detail penghasilan | JSON structure |
| `employee_expense` | jsonb | Detail potongan | JSON structure |
| `final_salary` | integer | Gaji bersih | Rupiah |

## �📊 Enhanced Analytics

### Work Hours Tracking
- **Total Work Hours**: Dari kolom `workhours` di tabel attendances
- **Average Daily Hours**: Rata-rata jam kerja per hari
- **Work Efficiency**: Perbandingan actual vs expected hours
- **Overtime Analysis**: Tracking lembur dari tabel overtimes

## Scheduling Otomatis

### Production Setup
Sistem akan otomatis generate payroll setiap tanggal 1 jam 00:00.

Pastikan cron job Laravel berjalan:
```bash
# Tambahkan ke crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Development Testing
```bash
# Test scheduling manually
php artisan schedule:run

# List scheduled commands
php artisan schedule:list
```

## Notifications

### Admin Notifications
- **Success**: Payroll berhasil di-generate
- **Failure**: Error dalam proses generation
- **Summary**: Detail hasil generation (jumlah processed, gagal, dll)

### Notification Channels
- **Mail**: Email ke admin
- **Database**: In-app notification di Filament

## Troubleshooting

### Common Issues
1. **Queue not processing**: Pastikan `php artisan queue:work` berjalan
2. **Missing attendance data**: Cek kelengkapan data attendance
3. **Calculation errors**: Review employee income/expenses structure
4. **Scheduling not working**: Pastikan cron job Laravel aktif

### Debug Commands
```bash
# Cek status queue
php artisan queue:status

# Restart queue workers
php artisan queue:restart

# Check failed jobs
php artisan queue:failed
```

## Security & Performance

### Best Practices
- Gunakan queue untuk bulk operations
- Monitor failed jobs secara berkala  
- Backup data sebelum bulk generation
- Test di environment staging dulu

### Performance Tips
- Batch process untuk data besar
- Index database untuk performance
- Monitor memory usage untuk bulk operations
