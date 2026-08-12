# Work Orders API Documentation

## Authentication
- Endpoint `/api/work-orders` dan turunannya membutuhkan autentikasi (Bearer Token, Sanctum)
- Endpoint `/api/public-survey/...` dapat diakses tanpa autentikasi

---

## Work Order Endpoints

### 1. List Work Orders
**GET** `/api/work-orders`

**Query Parameters:**
- `status` (optional): Filter by status (Open, Pending, Hold Confirm, Confirm, Assigned, On Progress, Closed, Cancelled)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "service_id": 2,
      "assigned_id": 5,
      "status": "Assigned",
      "work_date": "2025-06-13",
      // ...other fields
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### 2. Get Work Order Detail
**GET** `/api/work-orders/{workOrder}`

**Response:**
Detail lengkap work order beserta relasi (service, assigned, progress, workOrderPackage).

---

### 3. Update Work Order Status
**PATCH** `/api/work-orders/{workOrder}/status`

**Body:**
```json
{
  "status": "On Progress"
}
```
**Response:**
Work order terbaru setelah update status.

---

### 4. Add Progress to Work Order
**POST** `/api/work-orders/{workOrder}/progress`

**Body:**
- `progress_status`: (required) Salah satu dari: Take Order, Ketemu Client, Survey, Mulai Kerja, Tindakan, Selesai Kerja, Collect Money
- `notes`: (optional) Catatan
- `photos[]`: (optional) Array file gambar (max 5MB per file)
- `location`: (optional) Object `{ "latitude": ..., "longitude": ... }`

**Response:**
Progress terbaru yang berhasil ditambahkan.

---

### 5. Get All Progress of Work Order
**GET** `/api/work-orders/{workOrder}/progress`

**Response:**
Array seluruh progress work order.

---

### 6. Get Latest Progress of Work Order
**GET** `/api/work-orders/{workOrder}/progress/latest`

**Response:**
Progress terakhir work order.

---

## Survey API (Authenticated)

### 7. Get Survey Form Template
**GET** `/api/work-orders/forms/{type}`

- `{type}`: identification, initial_check, final_check

**Response:**
Template form survey beserta field dinamis.

---

### 8. Submit Survey
**POST** `/api/work-orders/{workOrder}/surveys`

**Body:**
```json
{
  "survey_form_id": 1,
  "answers": {
    "field_id_1": "value",
    "field_id_2": "value"
  }
}
```
**Response:**
Survey yang berhasil disimpan.

---

### 9. Get Survey Result
**GET** `/api/work-orders/{workOrder}/surveys/{type}`

**Response:**
Hasil survey untuk work order dan tipe tertentu.

---

## Public Survey API (No Auth)

### 10. Get Survey Form Template (Public)
**GET** `/api/public-survey/form/{type}`

### 11. Submit Survey (Public)
**POST** `/api/public-survey/work-orders/{workOrder}/submit`

### 12. Get Survey Result (Public)
**GET** `/api/public-survey/work-orders/{workOrder}/survey/{type}`

---

## Catatan
- Semua endpoint yang mengakses/mengubah work order hanya dapat dilakukan oleh user yang di-assign pada work order tersebut.
- Untuk endpoint public, pastikan Anda mengatur proteksi tambahan jika diperlukan (misal: kode unik, dsb).
- Untuk upload file, gunakan `multipart/form-data`.
- Semua response error akan menggunakan format standar Laravel API.

---

Jika Anda ingin dokumentasi dalam format Postman Collection atau OpenAPI/Swagger, silakan informasikan!
