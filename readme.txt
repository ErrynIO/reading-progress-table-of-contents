=== Erryn Reading Progress & Table of Contents ===
Contributors: errynio
Tags: reading progress, table of contents, toc, progress bar, navigation
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a fast reading progress bar and automatic table of contents to WordPress posts and pages.

== Description ==

Erryn Reading Progress & Table of Contents helps readers understand where they are in a long page and move directly to the section they need.

It automatically builds a table of contents from your headings, shows reading progress as the visitor scrolls, and highlights the current section.

It works immediately after activation, follows your theme's colours in light and dark mode, and provides sensible defaults without requiring shortcodes or manual setup.

= Two responsive layouts =

Choose between:

* **Horizontal bar**: a progress bar with section links, fixed at the top or bottom of the viewport.
* **Side rail**: a compact column of section markers that expands into the full contents list on hover or keyboard focus.

On smaller screens, the side rail automatically switches to the horizontal bar.

= Automatic or manual control =

By default, the plugin detects headings and displays itself automatically when a post contains enough sections.

You can also:

* Add the **TOC & Progress** block to enable it explicitly on a page.
* Use the `[rptoc_reading_progress_toc]` or `[rptoc]` shortcode.
* Add **TOC Anchor** blocks to define your own sections and labels.
* Force the component on or off for an individual post.
* Select which post types and heading levels should be included.
* Exclude individual posts.
* Provide a custom content-area selector for unusual themes.

When a page contains a TOC Anchor block, the plugin uses the manual anchors instead of generating its contents from headings.

= Designed to fit your theme =

The plugin can inherit colours from your active theme, including sites that switch between light and dark mode.

You may also choose colours from the theme palette or enter custom colours and opacity values.

A live contrast check warns when custom text and background colours may be difficult to read.

Appearance options include:

* Rounded pills or plain-text links.
* Start, centre or end alignment.
* Desktop and mobile font sizes.
* Adjustable pill spacing.
* Progress fill above or below the links.
* Top or bottom placement.
* Automatic or custom sticky-header offset.
* An optional drop shadow, off by default.

= Fast by design =

The plugin avoids repeatedly measuring the page during an ordinary scroll.

It measures section positions when required, stores those measurements, and updates the progress indicator through `requestAnimationFrame`. The active section is tracked with `IntersectionObserver`, keeping that work out of the scroll handler.

There is no jQuery dependency and no frontend build framework.

= Accessibility =

The component uses ordinary anchor links, supports keyboard interaction and identifies the active section with `aria-current`.

Section links continue to work without JavaScript. JavaScript is required for the progress indicator, active-section highlighting and expandable side rail.

Right-to-left layouts are supported.

= Structured data and SEO plugins =

Optional ItemList structured data can provide a machine-readable description of the page sections.

The plugin also registers as a recognised table of contents for Rank Math's content analysis, and can run alongside Yoast SEO and All in One SEO.

= Privacy =

Erryn Reading Progress & Table of Contents does not collect, store or send visitor data.

There are no accounts, external API requests or tracking scripts.

= Features =

* Automatic table of contents from H2 to H6 headings.
* Reading progress indicator.
* Current-section highlighting.
* Horizontal bar and side-rail layouts.
* Responsive mobile behaviour.
* Automatic theme colour inheritance.
* Light- and dark-mode support.
* Theme-palette and custom colour options.
* Per-colour opacity controls.
* Live colour-contrast warnings.
* Top or bottom viewport placement.
* Automatic or custom header offset.
* Pill or plain-text links.
* Responsive font-size controls.
* Optional drop shadow, off by default.
* TOC Anchor block for manually defined sections.
* TOC & Progress block.
* Shortcode support.
* Per-post display controls.
* Optional ItemList structured data.
* Rank Math table-of-contents recognition.
* WPML and Polylang support.
* Translation template included.
* Right-to-left support.
* `aria-current` on the active link.
* No jQuery.
* No tracking.

For screenshots, a live demonstration and further technical details, visit the plugin page:

https://erryn.io/products/erryn-reading-progress-table-of-contents/

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/erryn-reading-progress-table-of-contents/`, or install the plugin through the WordPress Plugins screen.
2. Activate **Erryn Reading Progress & Table of Contents**.
3. Open a post or page containing several headings.
4. Visit **Settings > Reading Progress** to change the layout, placement or appearance.

The plugin works with its default settings immediately after activation.

== Frequently Asked Questions ==

= Do I need to configure anything? =

No. Activate the plugin and it will appear automatically on supported posts and pages that contain enough headings.

All settings are optional.

= Can I turn it off on one post? =

Yes. Open the Reading Progress & Contents panel in the post editor and choose **Never show**.

You can also choose **Always show** to override the global automatic-insertion rules for that post.

= Can I define the sections myself? =

Yes. Add TOC Anchor blocks where you want entries to appear.

Once a page contains one TOC Anchor block, the plugin uses the manual anchors instead of generating its contents from headings.

= Can I use it outside the block editor? =

Yes. Use the `[rptoc_reading_progress_toc]` shortcode. The shorter `[rptoc]` version is also available.

It can be used in the Classic Editor and page builders that process standard WordPress shortcodes.

= What happens on mobile? =

The horizontal bar remains available on smaller screens.

When the side-rail layout is selected, it automatically switches to the horizontal bar on mobile devices.

= Does it modify my saved content? =

No.

The plugin adds IDs and its own markup to the rendered page. It does not permanently rewrite the content stored in the WordPress editor.

= Will it work with a sticky header? =

The plugin attempts to detect the site header automatically.

You can also set a custom header offset in pixels when automatic detection does not suit the theme.

= Can I use a custom content container? =

Yes.

Under the advanced detection settings, choose the custom selector option and provide the CSS selector for the content area, such as:

`article .entry-content`

= Does it collect any data? =

No. The plugin does not track visitors or send data to an external service.

= Does it work without JavaScript? =

The table-of-contents links remain ordinary anchor links and can still be used.

JavaScript is required for the progress fill, active-section highlighting and side-rail interaction.

= Can I customise it with CSS? =

Yes.

The plugin uses CSS custom properties for its colours and spacing, allowing developers to restyle it without editing the plugin files.

= Does the structured data guarantee search-result jump links? =

No.

The optional ItemList markup provides a machine-readable description of the page sections, but it does not guarantee a particular search-result treatment or ranking improvement.

== Screenshots ==

1. The horizontal reading-progress bar with the current section highlighted.
2. The side rail expanded into the full table of contents.
3. General settings for automatic insertion, headings and post types.
4. Placement controls for the bar, rail and header offset.
5. Appearance settings for link style, sizing and spacing.
6. Theme, palette and custom colour controls with the live contrast check.
7. Per-post controls in the WordPress editor.
8. The TOC & Progress and TOC Anchor blocks in the block editor.

== Changelog ==

= 1.0.2 =

* Fixed the Plugin URI pointing to a page that does not exist.
* Renamed the reading_progress_toc shortcode to rptoc_reading_progress_toc for a consistent, unique prefix.

= 1.0.1 =

* Fixed the section links not scrolling sideways on mobile when the side-rail layout falls back to the bar.

= 1.0.0 =

* Initial release.
* Automatic table-of-contents generation from H2 to H6 headings.
* Horizontal reading-progress bar and expandable side-rail layouts.
* Active-section highlighting and responsive mobile behaviour.
* Theme-derived, palette and custom colour options, with a live contrast check.
* Block, shortcode and per-post controls.
* Manual TOC Anchor blocks.
* Optional ItemList structured data.
* Rank Math recognition, alongside Yoast and All in One SEO.
* WPML, Polylang and right-to-left support.

== Upgrade Notice ==

= 1.0.2 =

Rename of the reading_progress_toc shortcode to rptoc_reading_progress_toc. Update any posts using the old tag.

= 1.0.1 =

Fixes horizontal scrolling of the section links on mobile.

= 1.0.0 =

Initial public release.
