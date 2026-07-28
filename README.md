# Reading Progress & Table of Contents

A performance-conscious WordPress plugin that adds an automatic table of contents, reading progress and current-section highlighting to long posts and pages.

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
- No jQuery, external API requests or visitor tracking

## Installation

### WordPress.org

Once the directory release is available, search for **Reading Progress & Table of Contents** under **Plugins → Add New**.

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
- use `[reading_progress_toc]` or `[rptoc]` outside the block editor;
- add **TOC Anchor** blocks to define sections manually;
- force the component on or off for an individual post;
- provide a custom content-area selector for unusual themes.

Full documentation, screenshots and a live demonstration are available on the [product page](https://erryn.io/products/reading-progress-table-of-contents/).

## Development and support

Use the WordPress.org support forum for general usage questions once the plugin is listed there.

Use [GitHub Issues](https://github.com/ErrynIO/reading-progress-table-of-contents/issues) for reproducible bugs and development-related reports. Please read [SECURITY.md](SECURITY.md) before reporting a suspected vulnerability.

## Privacy

The plugin does not collect, store or send visitor data. It contains no accounts, external API requests or tracking scripts.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Licence

Reading Progress & Table of Contents is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE).
