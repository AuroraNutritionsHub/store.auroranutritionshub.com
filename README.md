# Gym Store API & Admin Panel

Production-ready PHP REST API and React Single Page Application (SPA) designed for deployment on shared or dedicated hosting environments (Windows Plesk / IIS / Apache / cPanel).

## 🚀 Features

- **PHP 8.1+ REST API**: High-performance, zero heavy external framework dependencies.
- **Role-Based Access Control**:
  - `ADMIN`: Full administrative control across gyms, products, staff, and reports.
  - `BILLER`: Onsite POS billing for walk-in gym customers with receipt generation and stock synchronization.
  - `USER`: Mobile app access scoped to gym members for browsing products, ordering, and UPI payments.
- **Real-time Notifications**: Engine.IO / Socket.IO long-polling endpoint for live admin notification pushes.
- **Embedded Admin Dashboard**: Pre-built React Single Page Application in `public/` routed seamlessly through `index.php`.
- **Database**: MySQL / Percona / MariaDB with full foreign key constraints and transaction support.

## 📁 Project Structure

```
├── .env.example          # Environment variables template
├── .htaccess             # Apache rewrite configuration
├── .user.ini             # CGI/FastCGI PHP settings
├── index.php             # Application entry point, router & static file server
├── web.config            # IIS / Windows Plesk URL rewrite rules
├── gym_store.sql         # Complete MySQL schema & initial seed data
├── public/               # React Admin Panel build (HTML, CSS, JS)
├── src/
│   ├── controllers/      # API controller endpoints
│   ├── lib/              # Core utilities (DB, JWT Auth, Request/Multipart, Socket)
│   ├── services/         # Order pricing, sequential numbering & notifications
│   └── routes.php        # API route definitions
└── uploads/              # Storage directory for logos, product images & receipts
```

## ⚙️ Configuration

Copy `.env.example` to `.env` and fill in your database and environment settings:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gym_store
DB_USER=root
DB_PASSWORD=

JWT_SECRET=your_secret_key_here
JWT_EXPIRES_IN=7d
DELIVERY_CHARGE=50
```

## 🛠️ Deployment on Windows Plesk (IIS)

1. Set domain PHP version to **PHP 8.1** or **PHP 8.2** (FastCGI).
2. Ensure IIS URL Rewrite module is active.
3. Import `gym_store.sql` into MySQL.
4. Upload project files into the website root directory.
5. Ensure the `uploads/` folder has Write permissions.
