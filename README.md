# Digital Marketing Display (DMD)

Core PHP MVC foundation — scalable structure, ready for Laravel migration later.

## Structure

```
/project-root
    /app
        /controllers
        /models
        /views
        /core       # Router, Controller, Model, Database, Autoloader, ErrorHandler
        /helpers
    /public
        /assets (css, js, images, uploads)
        index.php   # Front controller
    /config
    /routes
    /storage
    /logs
```

## Setup

1. Point Apache document root to `public/` or use project root with the included `.htaccess` (rewrites to `public/`).
2. Copy `.env.example` to `.env` and set DB credentials if needed.
3. Create database `dmd` in MySQL (or set `DB_NAME` in `.env`).

## Run

- **With docroot = `public`:** `http://localhost/dmd/public/`
- **With docroot = project:** `http://localhost/dmd/` (root `.htaccess` forwards to `public/`)
