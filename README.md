# DAR-iLAND

**DAR-iLAND: A Web-Based Integrated Land Administration Network Database  
for the DAR Negros Oriental Provincial Office**

## Project Overview
DAR-iLAND is a government-grade web-based system designed to manage, validate, record, and visualize landholdings and land transfer clearance applications under the Department of Agrarian Reform (DAR). The system supports audit-ready records, role-based access, and validation assistance for agrarian laws such as the 5-hectare landholding rule.

This project is developed as a **BS Information Technology capstone thesis** at the Asian College of Science and Technology – Dumaguete City.

---

## Technology Stack
- **Backend:** Laravel (PHP)
- **Database:** PostgreSQL
- **Frontend:** Blade Templates (Laravel)
- **Version Control:** Git & GitHub

---

## Repository Rules (IMPORTANT)
- The `main` branch contains the **stable baseline system**
- Only approved changes should be pushed to `main`
- Developers must **pull from `main` before working**
- Practice CRUD, experiments, and tests should be done **locally or on separate branches**

---

## System Status (Week 1)
✅ Laravel project initialized  
✅ PostgreSQL connected  
✅ Core database tables created  
✅ Sample data seeded  
✅ Basic list pages working  

---

## Initial Database Tables
- `users` (with role field)
- `landholdings`
- `land_transfer_applications`

---

## Local Setup Instructions

### 1. Clone the Repository
```bash
git clone <REPOSITORY_URL>
cd DAR-iLAND
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database (PostgreSQL)
Create database:
```sql
CREATE DATABASE dar_iland;
```

Update `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dar_iland
DB_USERNAME=your_pg_username
DB_PASSWORD=your_pg_password
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Seed Sample Data
```bash
php artisan db:seed --class=DemoSeeder
```

### 7. Run the Application
```bash
php artisan serve
```

Open:
http://127.0.0.1:8000

---

## Test Pages
- `/landholdings`
- `/applications`

---

## Proponent
**Cuenca, Jake Kevin Klair L.**  
BS Information Technology  
Asian College of Science and Technology – Dumaguete City  
2026
