# Erryn Reading Progress & Table of Contents

A performance-conscious WordPress plugin that adds an automatic table of contents, reading progress and current-section highlighting to long posts and pages.

> This repository is named `reading-progress-table-of-contents`. The plugin's WordPress.org listing and display name are **Erryn Reading Progress & Table of Contents**, at the slug `erryn-reading-progress-table-of-contents`, so that it is distinctive within the WordPress.org directory. The two names refer to the same plugin.

[View the product page and live demonstration](https://erryn.io/products/reading-progress-table-of-contents/)

## Features

- Automatic table of contents generated from H2–H6 headings
- Horizontal reading-progress bar and expandable side rail
- Current-section highlighting
- Responsive mobile behaviour
- Theme-aware colours with light- and dark-mode support
- Theme-palette and custom colour options with contrast warnings
- Block, shortcode and per-post controls
- Manual TOC Anchor blocks for custom sections and labels
- Optional ItemList structured data
- Rank Math table-of-contents recognition
- WPML, Polylang and right-to-left support
- Minified frontend script and stylesheet, full source shipped alongside
- No jQuery, external API requests or visitor tracking

## Installation

### WordPress.org

Search for **Erryn Reading Progress & Table of Contents** under **Plugins → Add New**.

### Manual installation

1. Download the latest installable ZIP from the [Releases](https://github.com/ErrynIO/reading-progress-table-of-contents/releases) page.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate the plugin.
4. Visit **Settings → Reading Progress** to adjust its layout, placement or appearance.

The plugin works with its default settings immediately after activation.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later

## Usage

By default, the plugin detects headings and appears automatically when a supported post contains enough sections.

You can also:

- add the **TOC & Progress** block to enable it explicitly;
- use `[rptoc_reading_progress_toc]` or `[rptoc]` outside the block editor;
- add **TOC Anchor** blocks to define sections manually;
- force the component on or off for an individual post;
- provide a custom content-area selector for unusual themes.

Full documentation, screenshots and a live demonstration are available on the [product page](https://erryn.io/products/reading-progress-table-of-contents/).

## Contributing to the frontend assets

`assets/js/toc-progress.js` and `assets/css/toc-progress.css` are the source: readable, commented, the files to actually edit. Each has a `.min` counterpart alongside it, which is what a live site loads by default; the plugin switches back to the full source automatically when `SCRIPT_DEBUG` is `true`, the same convention WordPress core itself uses.

There's no build step to develop against: no watcher, no bundler, edit the source files directly. The `.min` files are regenerated only when preparing a release, with [Terser](https://github.com/terser/terser) for the script and [clean-css](https://github.com/clean-css/clean-css) for the stylesheet, and are committed alongside the source so the repository always reflects exactly what a given tagged version ships.

`assets/js/admin.js`, `assets/css/admin.css` and both block `editor.js` files only ever load in `wp-admin`, so they're left unminified.

## Development and support

Use the WordPress.org support forum for general usage questions.

Use [GitHub Issues](https://github.com/ErrynIO/reading-progress-table-of-contents/issues) for reproducible bugs and development-related reports. Please read [SECURITY.md](SECURITY.md) before reporting a suspected vulnerability.

## Privacy

The plugin does not collect, store or send visitor data. It contains no accounts, external API requests or tracking scripts.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Licence

Erryn Reading Progress & Table of Contents is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE).
