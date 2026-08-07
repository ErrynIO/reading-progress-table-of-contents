# Changelog

All notable changes to Erryn Reading Progress & Table of Contents will be documented in this file.

## 1.0.3 — 2026-08-07

### Changed

- The frontend script and stylesheet are now minified for production; the plugin loads the full source instead when `SCRIPT_DEBUG` is enabled
- Moved two comment-only asides out of the frontend files and into files that only ever load in `wp-admin`, so they no longer add to the weight of every page load

## 1.0.2 — 2026-08-07

### Fixed

- Plugin URI pointing to a page that did not exist

### Changed

- Renamed the `reading_progress_toc` shortcode to `rptoc_reading_progress_toc` for a consistent, unique prefix

## 1.0.1 — 2026-08-01

### Fixed

- Section links not scrolling horizontally on mobile when the side-rail layout falls back to the horizontal bar

## 1.0.0 — 2026-07-28

### Added

- Automatic table of contents generated from H2–H6 headings
- Horizontal reading-progress bar
- Expandable side-rail layout
- Current-section highlighting
- Responsive mobile behaviour
- Theme-derived, palette and custom colour options
- Live custom-colour contrast warnings
- Block, shortcode and per-post display controls
- Manual TOC Anchor blocks
- Optional ItemList structured data
- Rank Math table-of-contents recognition
- WPML, Polylang and right-to-left support
- Translation template
