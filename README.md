# Weather Block

A WordPress Gutenberg plugin that renders a **magazine-style layout** — one featured post card, two secondary post cards, with an optional live **OpenWeatherMap weather widget** in the third slot.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Block Usage](#block-usage)
- [Admin Settings](#admin-settings)
- [WP-CLI Commands](#wp-cli-commands)
- [Project Structure](#project-structure)
- [Architecture Overview](#architecture-overview)
- [Build System](#build-system)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Security Considerations](#security-considerations)

---

## Features

| Area | Details |
|------|---------|
| **Gutenberg Block** | API v3 dynamic block, FSE-compatible, supports wide/full align, spacing, colour, and typography controls |
| **Magazine Layout** | Featured post (large) + two secondary posts in a responsive CSS Grid; collapses to a single column on mobile |
| **Post Cards** | Editor picks up to 3 posts via an async search picker; cards show thumbnail, category, title, and excerpt |
| **Weather Widget** | Optional — replaces the third post slot; fetched client-side via authenticated AJAX |
| **Visibility Controls** | 9 individual toggles to show/hide each weather field |
| **Admin Settings** | `Settings › Weather Block` page to store the OpenWeatherMap API key securely |
| **Caching** | Transient-based, 1-hour TTL, keyed per lat/lon pair — API key never exposed to the browser |
| **WP-CLI** | `wp weather-block clear-cache` to invalidate cached weather data |
| **Tests** | 39 JS unit tests (Jest) + 34 PHP unit tests (PHPUnit + Brain/Monkey) |

---

## Requirements

| Dependency | Minimum version |
|-----------|----------------|
| PHP | 8.1 |
| WordPress | 6.4 |
| Node.js | 18 |
| npm | 9 |
| Composer | 2 |

---

## Installation

1. Copy the plugin directory into `wp-content/plugins/`.
2. Run `composer install` inside the plugin directory.
3. Activate **Weather Block** from the WordPress admin Plugins screen.

The `build/` directory is committed, so no Node.js build step is required to use the plugin. If you want to modify the source, run `npm install && npm run build` after making changes.

---

## Block Usage

1. Open any post or page in the Gutenberg editor.
2. Insert the **Weather Block** block (category: _Widgets_).
3. In the **sidebar**:
   - **Post Selection** — search and pick the featured post, second post, and optionally a third post.
   - **Weather Location** — enter decimal latitude and longitude (e.g. `50.4501`, `30.5234`). When set, the weather widget replaces the third post slot.
   - **Weather Fields** — toggle individual weather data points on or off (only visible when coordinates are set).
4. Save/publish. The block renders the post cards and, if coordinates are set, fetches live weather on page load.

### Weather fields available

| Toggle | Data shown |
|--------|-----------|
| Location name | City/location from API |
| Temperature | Current temp in °C |
| Feels-like temperature | Apparent temp in °C |
| Weather condition | Descriptive text (e.g. "clear sky") |
| Humidity | Percentage |
| Pressure | hPa |
| Wind speed | m/s |
| Sunrise | Local time HH:MM |
| Sunset | Local time HH:MM |

---

## Admin Settings

Navigate to **Settings › Weather Block** to enter your [OpenWeatherMap](https://openweathermap.org/api) API key.

- The key is sanitized with `sanitize_text_field()` before storage.
- Stored via `register_setting()` / `get_option()` — never serialised into block attributes.
- The key is **never** included in frontend HTML or JavaScript; all API calls are proxied through a server-side AJAX handler.

---

## WP-CLI Commands

### Clear all weather caches

```bash
wp weather-block clear-cache
```

Deletes every transient whose key begins with `weather_block_`.

### Clear cache for a specific location

```bash
wp weather-block clear-cache --lat=50.4501 --lon=30.5234
```

Removes only the transient for that lat/lon pair.

> **Note:** Both `--lat` and `--lon` must be supplied together; providing only one results in an error.

---

## Project Structure

```
weather-block/
│
├── weather-block.php           # Plugin header, constants, bootstrap
├── block.json                  # Block metadata (API v3)
├── composer.json               # PSR-4 autoloading + PHP dev deps
├── package.json                # npm scripts + JS dependencies
├── webpack.config.js           # Extends @wordpress/scripts; adds `frontend` entry
├── jest.config.js              # Jest — extends @wordpress/scripts preset
├── phpunit.xml.dist            # PHPUnit 10 configuration
├── phpcs.xml.dist              # WordPress Coding Standards ruleset
├── .eslintrc.json              # ESLint — @wordpress/recommended + jest env
├── .stylelintrc.json           # Stylelint — @wordpress/scss + BEM pattern
│
├── includes/                   # PHP source (namespace: WeatherBlock\)
│   ├── Plugin.php              # Singleton orchestrator
│   ├── Block/
│   │   └── WeatherBlock.php    # register_block_type + render_callback
│   ├── API/
│   │   └── WeatherEndpoint.php # wp_ajax handler, OpenWeatherMap, transients
│   ├── Settings/
│   │   └── AdminPage.php       # Settings API — API key storage
│   └── CLI/
│       └── WeatherCommand.php  # WP-CLI: clear-cache subcommand
│
├── src/                        # JavaScript / SCSS source
│   ├── index.js                # Block registration entry point
│   ├── edit.js                 # React editor component
│   ├── save.js                 # Returns null (dynamic / server-rendered block)
│   ├── style.scss              # Frontend + editor styles
│   ├── editor.scss             # Editor-only styles (placeholders, states)
│   ├── components/
│   │   ├── PostSelector.js           # ComboboxControl + core-data store search
│   │   ├── PostCardPreview.js        # Editor canvas preview of selected post
│   │   └── WeatherVisibilityControls.js  # 9 ToggleControls for weather fields
│   └── frontend/
│       ├── index.js            # Frontend entry — AJAX fetch + DOM injection
│       └── utils.js            # esc(), buildWidget() — exported for testing
│
├── build/                      # Compiled assets (generated, not committed)
│   ├── index.js                # Editor bundle
│   ├── index.css               # Editor-only styles
│   ├── style-index.css         # Frontend + editor styles
│   ├── frontend.js             # Frontend weather widget bundle
│   └── *.asset.php             # Dependency manifests (auto-generated)
│
├── tests/
│   └── php/
│       ├── bootstrap.php
│       └── Unit/
│           ├── API/WeatherEndpointTest.php
│           └── CLI/WeatherCommandTest.php
│
└── languages/                  # Translation-ready (.pot files go here)
```

---

## Architecture Overview

### PHP

```
plugins_loaded
    └── Plugin::get_instance()->init()
            ├── WeatherBlock::register()          add_action('init', register_block_type)
            ├── WeatherEndpoint::register()        wp_ajax_weather_block_get_weather (auth + nopriv)
            ├── AdminPage::register()              add_options_page + register_setting
            └── WP_CLI::add_command('weather-block', WeatherCommand)
```

**WeatherEndpoint flow:**

```
POST admin-ajax.php
  action=weather_block_get_weather
  nonce=<nonce>
  lat=<lat>  lon=<lon>
        │
        ├── check_ajax_referer()         — verifies nonce
        ├── validate lat / lon           — numeric, range check
        ├── get_transient(cache_key)     — return cached if fresh
        ├── get_option('weather_block_api_key')
        ├── wp_remote_get(OWM API URL)   — server-side, key not exposed
        ├── normalize()                  — sanitize + round values
        ├── set_transient(1 hour)
        └── wp_send_json_success($data)
```

### JavaScript

The editor bundle (`build/index.js`) is loaded only in the block editor. It uses `@wordpress/data` to search for posts without leaving the editor.

The frontend bundle (`build/frontend.js`) is a **framework-free** script (~3 KB minified) registered with `wp_register_script()` and enqueued only on pages that contain the block with weather coordinates set. It:

1. Queries `.weather-block__weather` containers injected by the PHP render callback.
2. Reads `data-lat`, `data-lon`, `data-visibility` attributes.
3. Sends a `fetch()` POST to `admin-ajax.php` with a nonce.
4. Builds the widget HTML using `buildWidget()` (HTML-escaped via `esc()`).
5. Injects the result into the container.

Translated labels (`weatherBlockI18n`) and endpoint data (`weatherBlockData`) are passed via `wp_localize_script()` — never hardcoded.

---

## Build System

[`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) v30 is used for all asset compilation.

| Command | Description |
|---------|-------------|
| `npm run build` | Production webpack build (minified, hashed assets) |
| `npm start` | Development watch mode (source maps, no minification) |
| `npm run build:production` | **Full CI pipeline:** lint JS → lint CSS → unit tests → production build |
| `npm run check` | Lint JS + lint CSS + unit tests (no build) |

### Entry points

| Entry | Output | Loaded |
|-------|--------|--------|
| `src/index.js` | `build/index.js` | Editor only (via `editorScript` in block.json) |
| `src/style.scss` | `build/style-index.css` | Frontend + Editor (via `style` in block.json) |
| `src/editor.scss` | `build/index.css` | Editor only (via `editorStyle` in block.json) |
| `src/frontend/index.js` | `build/frontend.js` | Frontend only (registered manually in PHP) |

---

## Testing

### JavaScript — Jest

Uses `@wordpress/jest-preset-default` with `@testing-library/react` for component tests.

```bash
npm test                  # Run all tests once
npm run test:watch        # Interactive watch mode
npm run test:coverage     # Coverage report (lcov + text)
```

Test files live next to the source files they test, in `__tests__/` subdirectories.

| Suite | File | Tests |
|-------|------|-------|
| `esc()` utility | `src/frontend/__tests__/utils.test.js` | 9 |
| `buildWidget()` utility | `src/frontend/__tests__/utils.test.js` | 20 |
| `WeatherVisibilityControls` | `src/components/__tests__/WeatherVisibilityControls.test.js` | 7 |
| `PostSelector` | `src/components/__tests__/PostSelector.test.js` | 4 |

Coverage thresholds (enforced by `jest.config.js`):

```
branches   ≥ 60 %
functions  ≥ 70 %
lines      ≥ 70 %
statements ≥ 70 %
```

### PHP — PHPUnit + Brain/Monkey

[Brain/Monkey](https://brain-wp.github.io/BrainMonkey/) stubs WordPress functions so the tests run without a full WordPress install.

```bash
composer run test             # Run all PHPUnit tests
composer run test:coverage    # With coverage output (requires Xdebug or PCOV)
```

| Suite | File | Tests |
|-------|------|-------|
| Coordinate validation | `tests/php/Unit/API/WeatherEndpointTest.php` | 15 |
| Cache key | `tests/php/Unit/API/WeatherEndpointTest.php` | 4 |
| Data normalisation | `tests/php/Unit/API/WeatherEndpointTest.php` | 2 |
| `get_weather()` | `tests/php/Unit/API/WeatherEndpointTest.php` | 4 |
| CLI argument validation | `tests/php/Unit/CLI/WeatherCommandTest.php` | 4 |

---

## Code Quality

### PHP — WordPress Coding Standards

```bash
composer run lint       # phpcs — reports violations
composer run lint:fix   # phpcbf — auto-fixes where possible
```

Configuration in `phpcs.xml.dist`: `WordPress` ruleset, `text_domain` set to `weather-block`, prefix enforced as `weather_block` / `WeatherBlock`.

### JavaScript — ESLint

```bash
npm run lint:js         # Reports violations
npm run format          # Prettier auto-format (via wp-scripts)
```

Config in `.eslintrc.json`: `@wordpress/eslint-plugin/recommended`. Test files additionally enable the `jest` environment.

### CSS/SCSS — Stylelint

```bash
npm run lint:css        # Reports violations
```

Config in `.stylelintrc.json`: `@wordpress/stylelint-config/scss` with a custom `selector-class-pattern` that allows BEM double-underscore and double-hyphen selectors.

---

## Security Considerations

| Vector | Mitigation |
|--------|-----------|
| **XSS via weather data** | All API values passed through `sanitize_text_field()` server-side; client-side output through `esc()` before DOM injection |
| **OWM icon injection** | Icon code validated with `/^[a-z0-9]+$/i` — any non-alphanumeric value silently discarded |
| **CSRF on AJAX endpoint** | `check_ajax_referer('weather_block_nonce', 'nonce')` on every request |
| **API key exposure** | Key stored in `wp_options`, never serialised into block attributes, never localised to the browser |
| **Coordinate injection** | Numeric range validation before any API call |
| **Direct file access** | `if ( ! defined('ABSPATH') ) exit;` in plugin entry point |

---

## Author

**Nazar Kurnyk** — [LinkedIn](https://www.linkedin.com/in/nazar-k-745829263/)
