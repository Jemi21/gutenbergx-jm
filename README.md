# Gutendex Books API & Frontend Application

A complete implementation of a Project Gutenberg books API with a Vue.js frontend, built with Laravel and PostgreSQL.

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation & Setup](#installation--setup)
- [API Documentation](#api-documentation)
- [Frontend Application](#frontend-application)
- [Running Tests](#running-tests)
- [Docker Deployment](#docker-deployment)
- [Production Deployment](#production-deployment)
- [API Endpoints](#api-endpoints)
- [Project Structure](#project-structure)

## 🎯 Project Overview

This project implements:
1. **RESTful API** for querying Project Gutenberg books with advanced filtering, pagination, and sorting capabilities
2. **Vue.js Frontend** with infinite scroll, search functionality, and book browsing by genre
3. **PostgreSQL Database** with optimized indexes for performance
4. **Swagger/OpenAPI Documentation** for API exploration

## ✨ Features

### API Features
- ✅ Multi-criteria filtering (IDs, languages, mime-types, topics, authors, titles)
- ✅ Multiple filter values per criteria (comma-separated)
- ✅ Pagination (25 books per page max)
- ✅ Sorting by popularity (download count descending)
- ✅ Full-text search across titles and authors
- ✅ Case-insensitive partial matching
- ✅ Books with covers only filter
- ✅ Complete book metadata (title, authors, genre, languages, subjects, bookshelves, download links)

### Frontend Features
- ✅ Genre/category browsing page
- ✅ Book listing with infinite scroll
- ✅ Real-time search (title + author)
- ✅ Genre filter preservation during search
- ✅ Book preview in browser (HTML > PDF > TXT priority)
- ✅ Responsive design
- ✅ Books with covers only display

## 🛠 Tech Stack

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Database**: PostgreSQL 15+
- **Frontend**: Vue.js 3 (via CDN)
- **API Documentation**: L5-Swagger (OpenAPI 3.0)
- **Containerization**: Docker & Docker Compose
- **CI/CD**: GitHub Actions

## 📦 Prerequisites

Before starting, ensure you have:

- PHP 8.1 or higher
- Composer
- PostgreSQL 15+
- Node.js & NPM (optional, for asset compilation)
- Docker & Docker Compose (optional, for containerized setup)

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

## 🧪 Running Tests

Run PHPUnit tests:

```bash
./vendor/bin/phpunit
```

Or with coverage:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

## 🐳 Docker Deployment

### Using Docker Compose

1. **Build and start containers**:

```bash
docker-compose up -d --build
```

2. **Run migrations**:

```bash
docker-compose exec app php artisan migrate --force
```

3. **Generate Swagger docs**:

```bash
docker-compose exec app php artisan l5-swagger:generate
```

4. **Access the application**:

- Frontend: http://localhost:8000
- Database: localhost:5433 (externally) or `db:5432` (from app container)

### Docker Compose Services

- **app**: Laravel PHP-FPM application
- **web**: Nginx web server
- **db**: PostgreSQL 15 database

### Environment Variables for Docker

Set these in `docker-compose.yml` or `.env`:

```env
DB_HOST=db
DB_PORT=5432
DB_DATABASE=gutendex
DB_USERNAME=gutendex
DB_PASSWORD=gutendex
```

## 🌐 Production Deployment

### Option 1: Render.com

1. **Push code to GitHub**

```bash
git add .
git commit -m "Ready for deployment"
git push origin main
```

2. **Create PostgreSQL database on Render**
   - Dashboard → New → PostgreSQL
   - Choose "Starter" plan (free tier)
   - Note the connection details

3. **Create Web Service**
   - Dashboard → New → Web Service
   - Connect your GitHub repository
   - **Root Directory**: `.` (root of repository)
   - **Environment**: Docker
   - **Dockerfile Path**: `Dockerfile.render`

4. **Set Environment Variables**:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key
DB_CONNECTION=pgsql
DB_HOST=your-render-db-host
DB_PORT=5432
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

5. **After first deployment**, run migrations:

   - Open Render Shell → Run:
   ```bash
   php artisan migrate --force
   php artisan l5-swagger:generate
   ```

### Option 2: Fly.io

1. **Install Fly CLI**:

```bash
curl -L https://fly.io/install.sh | sh
```

2. **Login to Fly**:

```bash
fly auth login
```

3. **Initialize app**:

```bash
fly launch
```

4. **Create Postgres database**:

```bash
fly postgres create --name gutendex-db
fly postgres attach gutendex-db
```

5. **Set secrets**:

```bash
fly secrets set APP_KEY="base64:your-generated-key"
```

6. **Deploy**:

```bash
fly deploy
```

7. **Run migrations**:

```bash
fly ssh console -C "php artisan migrate --force"
fly ssh console -C "php artisan l5-swagger:generate"
```

### Option 3: Railway

1. Connect GitHub repository
2. Add PostgreSQL service
3. Deploy using Dockerfile.render
4. Set environment variables
5. Run migrations via Railway console

## 📡 API Endpoints

### Get Books

```
GET /api/books
```

**Query Parameters**:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `ids` | string | Comma-separated Gutenberg IDs | `ids=84,1342` |
| `languages` | string | Comma-separated language codes | `languages=en,fr` |
| `mime_type` | string | Comma-separated mime-types | `mime_type=text/plain` |
| `topic` | string | Comma-separated topics | `topic=child,infant` |
| `author` | string | Comma-separated author names | `author=tolstoy` |
| `title` | string | Title search term | `title=war` |
| `search` | string | Space-separated words (title OR author) | `search=tolstoy war` |
| `has_cover` | boolean | Only books with cover images | `has_cover=1` |
| `page` | integer | Page number (default: 1) | `page=2` |
| `limit` | integer | Items per page (max: 25) | `limit=25` |

**Example Request**:

```bash
curl "http://localhost:8000/api/books?languages=en,fr&topic=Fiction&has_cover=1&page=1"
```

**Example Response**:

```json
{
  "count": 150,
  "next": "http://localhost:8000/api/books?languages=en,fr&topic=Fiction&page=2",
  "previous": null,
  "results": [
    {
      "id": 84,
      "title": "Frankenstein",
      "authors": [
        {
          "name": "Shelley, Mary Wollstonecraft",
          "birth_year": 1797,
          "death_year": 1851
        }
      ],
      "genre": "Fiction",
      "languages": ["en"],
      "subjects": ["Gothic fiction", "Horror tales"],
      "bookshelves": ["Fiction", "Horror"],
      "downloads": 15000,
      "download_links": [
        {
          "mime_type": "text/html",
          "url": "https://www.gutenberg.org/ebooks/84.epub"
        },
        {
          "mime_type": "application/pdf",
          "url": "https://www.gutenberg.org/files/84/84-pdf.pdf"
        }
      ]
    }
  ]
}
```

### Get Genres

```
GET /api/genres
```

**Example Response**:

```json
{
  "count": 25,
  "results": [
    "Fiction",
    "Children's Literature",
    "Poetry",
    ...
  ]
}
```

## 📁 Project Structure

```
.
├── app/
│   └── Http/Controllers/Api/
│       └── BookController.php      # Main API controller
├── config/
│   └── l5-swagger.php              # Swagger configuration
├── database/
│   ├── migrations/                  # Database migrations
│   └── gutendex.dump               # PostgreSQL dump
├── docker/
│   └── nginx/
│       └── default.conf            # Nginx configuration
├── resources/
│   └── views/
│       └── app.blade.php           # Vue.js frontend
├── routes/
│   ├── api.php                     # API routes
│   └── web.php                     # Web routes
├── tests/
│   └── Feature/
│       └── BooksApiTest.php        # API tests
├── Dockerfile                      # Docker image for local dev
├── Dockerfile.render              # Docker image for Render.com
├── docker-compose.yml             # Docker Compose setup
└── README.md                       # This file
```

## 🔧 Configuration

### Database Indexes

The following indexes are automatically created via migration for optimal performance:

- `idx_books_downloads` - For sorting by popularity
- `idx_books_gutenberg_id` - For ID filtering
- `idx_lang_code` - For language filtering
- `idx_format_mime` - For mime-type filtering
- `idx_format_book` - For format joins
- `idx_bsj_book`, `idx_bb_book`, `idx_ba_book` - For relationship joins

### CORS Configuration

CORS is enabled by default. Configure in `config/cors.php` if needed.

## 🧑‍💻 Development

### Code Style

The project follows PSR-12 coding standards.

### Adding New Features

1. Create feature branch: `git checkout -b feature/new-feature`
2. Make changes and test
3. Commit: `git commit -m "Add new feature"`
4. Push: `git push origin feature/new-feature`
5. Create Pull Request

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👤 Author

[Your Name]

## 🙏 Acknowledgments

- Project Gutenberg for the book data
- Laravel community
- Vue.js team

## 📞 Support

For issues, questions, or contributions, please open an issue on GitHub.

---

**Ready to deploy?** Follow the [Production Deployment](#production-deployment) section above!
