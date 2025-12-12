# Project Structure

```
pm/
├── api/                          # API Endpoints
│   ├── auth/                    # Authentication APIs
│   │   ├── register.php         # User registration endpoint
│   │   ├── login.php            # User login endpoint
│   │   ├── logout.php           # User logout endpoint
│   │   └── me.php               # Get current user endpoint
│   └── roles/                   # Role management APIs
│       └── list.php             # List available roles
│
├── assets/                      # Static assets (CSS, JS, images)
│   └── (to be added)
│
├── config/                      # Configuration files
│   ├── config.php               # Application configuration
│   └── database.php             # Database configuration (auto-generated)
│
├── database/                    # Database scripts
│   └── schema.sql               # Database schema and default data
│
├── includes/                    # Shared PHP includes
│   ├── auth.php                 # Authentication helper functions
│   ├── database.php             # Database helper functions
│   └── install-check.php        # Installation check helper
│
├── install/                     # Installation wizard
│   └── index.php                # Installation wizard interface
│
├── public/                      # Public web root
│   ├── index.php                # Homepage/landing page
│   ├── login.php                # Login page
│   ├── register.php             # Registration page
│   ├── dashboard.php            # User dashboard
│   └── .htaccess                # Apache configuration for public directory
│
├── .htaccess                    # Main Apache rewrite rules
├── README.md                    # Project overview
├── INSTALLATION.md              # Installation guide
└── PROJECT_STRUCTURE.md         # This file

```

## Key Features

### API-Based Architecture
- All frontend-backend communication uses RESTful API endpoints
- JSON request/response format
- Located in `/api/` directory

### Multi-Role System
- Users can have multiple roles (Property Owner, Tenant, Property Manager, Admin)
- Many-to-many relationship via `user_roles` junction table
- Role-based access control helpers in `includes/auth.php`

### Installation System
- Web-based installer at `/install/`
- Creates database and tables automatically
- Configures database connection
- 3-step installation process

### Authentication Flow
1. User registers via `/register.php` → calls `/api/auth/register.php`
2. User logs in via `/login.php` → calls `/api/auth/login.php`
3. Session-based authentication
4. Protected routes check authentication via `requireLogin()`

## Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Frontend**: Bootstrap 5.3, Vanilla JavaScript
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Server**: Apache with mod_rewrite

## Next Steps for Development

1. Add property management features
2. Add tenant management
3. Add payment tracking
4. Add document management
5. Add reporting features
6. Add email notifications

