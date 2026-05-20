# Project Kantin - Folder Structure

## Root Directory
```
project-kantin/
├── auth.php                    # Authentication utilities
├── config.php                  # Global configuration
├── dashboard.php               # Seller dashboard
├── index.php                   # Home page
├── login.php                   # User/Owner/Seller login page
├── myTray.php                  # Shopping cart page
├── owner_panel.php             # Owner admin panel
├── page2.php                   # Menu browse page
├── register.php                # User registration
├── seller_auth.php             # Seller authentication utilities
├── seller_login.php            # Seller login page
├── seller_register.php         # Seller registration
├── note.md                     # Project notes
├── note2.md                    # Additional notes
├── presentation.md             # Project presentation
├── presentationRole.md         # Role-based presentation
│
├── api/                        # API endpoints
│   ├── get_item.php           # Get menu items
│   ├── item_crud.php          # Menu CRUD operations
│   ├── logout.php             # User logout
│   ├── menu.php               # Menu management
│   ├── owner_action.php       # Owner actions (seller/user/stand management)
│   ├── owner_logout.php       # Owner logout
│   ├── rate.php               # Rating functionality
│   ├── review.php             # Review management
│   ├── seller_logout.php      # Seller logout
│   ├── seller_profile.php     # Seller profile API
│   ├── stand_crud.php         # Stand CRUD operations
│   ├── toko_crud.php          # Store CRUD operations
│   └── tray.php               # Shopping cart API
│
├── assets/                     # Static assets
│   ├── css/
│   │   └── style.css          # Global stylesheet
│   ├── img/
│   │   ├── logosmkn-transparent.png  # School logo (transparent)
│   │   └── ...                # Other images
│   └── js/
│       └── script.js          # Global JavaScript
│
├── config/                     # Configuration files
│   ├── database.php           # Database configuration
│   ├── koneksi.php            # Database connection
│   └── proses.php             # Processing utilities
│
├── project/                    # Backup/alternate version
│   ├── auth.php
│   ├── config.php
│   ├── dashboard.php
│   ├── index.php
│   ├── login.php
│   ├── owner_panel.php
│   ├── page2.php
│   ├── register.php
│   ├── seller_auth.php
│   ├── seller_login.php
│   ├── seller_register.php
│   └── api/
│       ├── get_item.php
│       ├── item_crud.php
│       ├── logout.php
│       ├── menu.php
│       ├── owner_action.php
│       ├── owner_logout.php
│       ├── rate.php
│       ├── review.php
│       ├── seller_logout.php
│       └── stand_crud.php
│
└── uploads/                    # User-generated uploads
    ├── owner_profile/         # Owner profile pictures
    └── seller_profile/        # Seller profile pictures
```

## Key Directories Explained

### `/api/`
Contains all backend API endpoints for AJAX operations and data processing.
- **item_crud.php** - Menu item create/read/update/delete
- **stand_crud.php** - Food stand create/read/update/delete
- **owner_action.php** - Owner panel operations (seller approval, user deletion, etc.)
- **tray.php** - Shopping cart quantity/item management
- **review.php, rate.php** - User reviews and ratings

### `/assets/`
Frontend resources:
- **css/style.css** - Global styles with theme variables and responsive design
- **img/** - Images (logos, default images)
- **js/script.js** - Global JavaScript utilities

### `/config/`
Database and system configuration:
- **koneksi.php** - MySQL connection setup
- **database.php** - Database schema/initialization

### `/uploads/`
User-generated content storage:
- **owner_profile/** - Owner avatar images
- **seller_profile/** - Seller avatar images
- Also contains menu item photos and stand photos

### `/project/`
Appears to be a backup or alternate version of the main application files.
