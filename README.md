# Frumle - AI-Powered Codebase Analyzer for PHP

Analyze your PHP codebase with AI and generate comprehensive API documentation. Supports **Laravel**, **Symfony**, **CodeIgniter 4+**, **CakePHP**, **Yii**, **Laminas (Zend)**, **Slim**, and **Phalcon**.

## Installation

### Global Install (Recommended)

```bash
composer global require frumle/frumle
```

Make sure your Composer global bin directory is in your `PATH`:

```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

### Per-Project Install

```bash
composer require --dev frumle/frumle
```

Then run via:

```bash
vendor/bin/frumle
```

## Quick Start

```bash
# 1. Add your API key (get it from the Frumle dashboard)
frumle add-key <your-api-key>

# 2. Analyze your project
frumle

# 3. View results at https://frumle.tellecata.com
```

## Commands

### `frumle [directory]`

Analyze a codebase. Defaults to the current directory.

```bash
frumle                              # Analyze current directory
frumle ./src                        # Analyze specific directory
frumle --project-name my-api        # Custom project name
frumle --ignore tests,storage       # Ignore specific directories
```

### `frumle add-key <api-key>`

Add or update your API key. Verifies the key with the server before saving.

```bash
frumle add-key devdoc_abc123...
```

### `frumle login <api-key>`

Alias for `add-key`.

### `frumle status`

Check your API key status, quota, and usage statistics.

```bash
frumle status
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
frumle
```

Frumle detects Laravel via `artisan` and scans routes, controllers, models, middleware, and more.

### Symfony

```bash
cd /path/to/symfony-project
frumle
```

Detects Symfony via `config/packages/` and scans controllers with routing attributes/annotations.

### CodeIgniter 4

```bash
cd /path/to/codeigniter4-project
frumle
```

Detects CodeIgniter via `app/Config/App.php` and scans controllers with defined routes.

### Slim Framework

```bash
cd /path/to/slim-project
frumle
```

Detects Slim via `public/index.php` and scans route definitions and middleware.

## Requirements

- PHP 8.0+
- `ext-curl`
- `ext-json`

## Environment Variables

| Variable         | Description                    | Default                                        |
|------------------|--------------------------------|------------------------------------------------|
| `FRUMLE_API_URL` | Backend API URL (for testing)  | `https://dev-doc-726dc734499e.herokuapp.com`   |
| `FRUMLE_API_KEY` | API key (alternative to config)| —                                              |

## License

MIT
