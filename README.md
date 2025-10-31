## 🚀 Installation & Setup

### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/gutendex-api.git
cd gutendex-api
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gutendex
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 4: Import Database

Import the PostgreSQL database dump:

```bash
# Option 1: Using psql
psql -U your_username -d gutendex -f database/gutendex.dump

# Option 2: Using pg_restore
pg_restore -U your_username -d gutendex database/gutendex.dump
```

### Step 5: Run Migrations

```bash
php artisan migrate
```

This will create necessary indexes for optimal query performance.

### Step 6: Generate Swagger Documentation

```bash
php artisan l5-swagger:generate
```

### Step 7: Start the Development Server

```bash
php artisan serve
```

The application will be available at:
- **Frontend**: http://localhost:8000
- **API**: http://localhost:8000/api/books
- **Swagger UI**: http://localhost:8000/api/documentation
- **Genres API**: http://localhost:8000/api/genres

## 📚 API Documentation

### Swagger UI

Access the interactive API documentation at:

```
http://localhost:8000/api/documentation
```

### OpenAPI Specification

The OpenAPI specification is available at:

- **JSON**: `storage/api-docs/api-docs.json`
- **YAML**: `storage/api-docs/api-docs.yaml`

## 🎨 Frontend Application

### Features

1. **Genre Selection Page**
   - Displays all available book genres/bookshelves
   - Click any genre to browse books

2. **Books Listing Page**
   - Infinite scroll for loading more books
   - Search bar for filtering by title or author
   - Genre filter persists during search
   - Click any book to open in browser (prefers HTML > PDF > TXT)

### Access

Open http://localhost:8000 in your browser after starting the server.


## 👤 Author

Jitendra Mishra
