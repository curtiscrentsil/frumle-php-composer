# Frumle - AI-Powered Codebase Analyzer for PHP

Analyze your PHP codebase with AI and generate comprehensive API documentation. Supports **Laravel**, **Symfony**, **CodeIgniter 4+**, **CakePHP**, **Yii**, **Laminas (Zend)**, **Slim**, and **Phalcon**.

## Installation

```bash
composer require --dev frumle/frumle
```

> **Note:** Avoid `composer global require` if you also use Frumle for JavaScript, Python, or C# — global installs from different package managers can conflict. Use `vendor/bin/frumle` instead.

## Quick Start

```bash
# 1. Add your API key (get it from the Frumle dashboard)
vendor/bin/frumle add-key <your-api-key>

# 2. Analyze your project
vendor/bin/frumle

# 3. View results at https://frumle.tellecata.com
```

## Commands

### `vendor/bin/frumle [directory]`

Analyze a codebase. Defaults to the current directory.

```bash
vendor/bin/frumle                              # Analyze current directory
vendor/bin/frumle ./src                        # Analyze specific directory
vendor/bin/frumle --project-name my-api        # Custom project name
vendor/bin/frumle --ignore tests,storage       # Ignore specific directories
```

### `vendor/bin/frumle add-key <api-key>`

Add or update your API key. Verifies the key with the server before saving.

```bash
vendor/bin/frumle add-key devdoc_abc123...
```

### `vendor/bin/frumle login <api-key>`

Alias for `add-key`.

### `vendor/bin/frumle status`

Check your API key status, quota, and usage statistics.

```bash
vendor/bin/frumle status
```

## Supported Frameworks

| Framework       | Version | Auto-Detection           |
|-----------------|---------|--------------------------|
| Laravel         | 5.x+    | `artisan`, `routes/api.php` |
| Symfony         | 4.x+    | `config/packages/`, routing annotations |
| CodeIgniter     | 4.x+    | `app/Config/App.php`     |
| CakePHP         | 4.x+    | `src/Application.php`    |
| Yii             | 2.x+    | `config/web.php`         |
| Laminas (Zend)  | 3.x+    | `config/autoload/`       |
| Slim            | 4.x+    | `Slim\App` in `public/index.php` |
| Phalcon         | 4.x+    | `.htrouter.php`          |

## What Gets Analyzed

The scanner collects these file types from your project:

- `.php` — PHP source files (including `.blade.php` templates)
- `.json` — Configuration files (composer.json, etc.)
- `.yaml` / `.yml` — Symfony routes, config, Docker, etc.
- `.xml` — Configuration files
- `.env` — Environment configuration
- `.twig` — Twig templates
- `.neon` — Nette configuration
- `.ini` — PHP configuration
- `.md` — Documentation

### Default Ignored Directories

`vendor`, `node_modules`, `.git`, `storage`, `cache`, `var`, `tmp`, `temp`, `logs`, `dist`, `build`, `runtime`, `assets`

## Configuration

### API Key

Stored at `~/.frumle/config.json`. Shared across all Frumle tools (npm, Python, Maven, PHP).

### Project Configuration

A `frumle.config.json` file is created in your project root with detected base URLs:

```json
{
  "baseUrls": [
    {
      "environment": "local",
      "url": "http://localhost:8000"
    },
    {
      "environment": "production",
      "url": ""
    }
  ]
}
```

The local URL is auto-detected from:
- `.env` file (`APP_URL`, `APP_PORT`, `PORT`)
- Framework-specific config files
- Common framework defaults

Edit the `production` URL to enable production API testing in the dashboard.

## Framework Examples

### Laravel

```bash
cd /path/to/laravel-project
vendor/bin/frumle
```

Frumle detects Laravel via `artisan` and scans routes, controllers, models, middleware, and more.

### Symfony

```bash
cd /path/to/symfony-project
vendor/bin/frumle
```

Detects Symfony via `config/packages/` and scans controllers with routing attributes/annotations.

### CodeIgniter 4

```bash
cd /path/to/codeigniter4-project
vendor/bin/frumle
```

Detects CodeIgniter via `app/Config/App.php` and scans controllers with defined routes.

### Slim Framework

```bash
cd /path/to/slim-project
vendor/bin/frumle
```

Detects Slim via `public/index.php` and scans route definitions and middleware.

## Requirements

- PHP 8.0+
- `ext-curl`
- `ext-json`

## Environment Variables

| Variable         | Description                    | Default                                        |
|------------------|--------------------------------|------------------------------------------------|
| `FRUMLE_API_URL` | Backend API URL (for testing)  | `https://frumle-production.up.railway.app`   |
| `FRUMLE_API_KEY` | API key (alternative to config)| —                                              |

## License

MIT
