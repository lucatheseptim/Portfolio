# MyGalleryAlbum — Photo Gallery Web Application

A full-stack web application for creating and browsing photo albums, built with
Laravel. Users register, create albums, upload photos and organise everything by
category; visitors browse a public gallery.

Live demo: **https://mygalleryalbum.altervista.org/**

## Features

- **User accounts** — registration, login, password reset (Laravel's built-in auth)
- **Albums** — create, edit, delete, browse; each album belongs to a user
- **Photos** — upload and manage images, associated with an album
- **Categories** — albums are grouped into categories, manageable from the back end
- **Public gallery** — browsing without authentication, with filtering
  by album and by category
- **Admin area** — separate views for content management

## Technology stack

| | |
|---|---|
| Framework | Laravel 5.8 |
| Language | PHP 7 |
| Database | MySQL (Eloquent ORM, migrations) |
| Templating | Blade |
| Front end | HTML, CSS, JavaScript, Bootstrap |
| Asset build | Laravel Mix (webpack) |

## Project structure

```
app/
  Http/Controllers/     AlbumsController, GalleryController, PhotosController,
                        AlbumCategoryController, UserController, HomeController,
                        PageController, WelcomeController
  Models/               Album, Photo, AlbumsCategory, CalbumCategory
database/migrations/    users, password_resets, photos, albums, album_categories
resources/views/        admin, albums, auth, categories, gallery, images,
                        layouts, partials, user
routes/web.php          application routes
```

## Routing design

Three route groups keep concerns separated:

- **Authenticated area** (`middleware => auth`) — album management, user
  management and the admin views. Resource controllers handle the standard
  CRUD verbs; several routes constrain their parameters with regular
  expressions so identifiers are always numeric.
- **Gallery (public)** — prefixed with `gallery/`, exposes album listings and
  per-album image browsing without requiring a session. The root path renders
  the gallery index.
- **Authentication** — `Auth::routes()` provides the standard login and
  registration endpoints.

## Database schema

| Table | Purpose |
|---|---|
| `users` | account credentials and profile |
| `photos` | image records, linked to an album |
| `albums` | album metadata, owned by a user |
| `album_categories` | category definitions for grouping albums |
| `password_resets` | token storage for password recovery |

## Running locally

```bash
composer install
cp .env.example .env        # configure database credentials
php artisan key:generate
php artisan migrate
php artisan serve
```

Assets:

```bash
npm install
npm run dev                 # or: npm run production
```

## Notes

- `.env` (with real credentials) is intentionally **not** included in this
  repository; only `.env.example` is versioned. Database settings are read
  from the environment via `env('DB_*')`.
- The Public folder contains the `.htaccess` used for Apache deployment on
  shared hosting.

## Author

Luca Airoldi
