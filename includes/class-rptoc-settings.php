<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part iii of xi
 * ====================================================================
 *  Marf charged. The fight was brief, mostly because the rat had not
 *  expected a grown man to attack with a bar stool while shouting,
 *  "I've only just woken up."
 *
 *    [ Enemy Defeated: Highway Rat ]
 *    Experience gained:  12
 *    Loot acquired:      Tiny Crossbow
 *    Loot acquired:      Suspicious Cheese
 *    New Skill:          Improvised Furniture Combat, Level 1
 *
 *    Erryn pulled the bolt from his head. "You've dented the stool."
 *    "I won."
 *    "You've lowered the resale value."
 *
 *  -> continues in includes/class-rptoc-render.php
 * ====================================================================
 */

class RPTOC_Settings {

	const OPTION = 'rptoc_settings';

	/**
	 * Role => array( css var, built-in fallback hex ). The fallback hex is
	 * a last resort only, so the component looks sane for the instant
	 * before a palette or custom value resolves. It is not meant to be a
	 * colour anyone lives with.
	 */
	const COLOR_ROLES = array(
		'bg'              => array( '--rptoc-bg', '#14151a' ),
		'fg'              => array( '--rptoc-fg', '#f2f2f2' ),
		'accent'          => array( '--rptoc-accent', '#4fd1ff' ),
		'accent_contrast' => array( '--rptoc-accent-contrast', '#0b0b0d' ),
	);

	const HEADING_LEVELS = array( 'h2', 'h3', 'h4', 'h5', 'h6' );

	public static function defaults() {
		$defaults = array(
			'enabled'           => 1,
			'min_headings'      => 3,
			'post_types'        => array( 'post', 'page' ),
			'exclude_ids'       => '',
			'heading_levels'    => array( 'h2', 'h3' ),
			'exclude_class'     => '',
			'style'             => 'pills',
			'align'             => 'center',
			'layout'            => 'bar',
			'rail_side'         => 'right',
			'shadow'            => 0,
			'progress_position' => 'above',
			'dock'              => 'bottom',
			'header_selector'   => '',
			'header_offset_mode' => 'auto',
			'header_offset'     => 0,
			'content_detect'    => 'auto',
			'content_selector'  => '',
			'schema_enabled'    => 1,
			'font_size_mode'    => 'inherit',
			'font_size_desktop' => 15,
			'font_size_mobile'  => 14,
			'pad_y'             => 6,
			'pad_x'             => 12,
			'pad_y_mobile'      => 8,
			'pad_x_mobile'      => 14,
		);

		foreach ( self::COLOR_ROLES as $key => $meta ) {
			// One global source now governs all four roles; per role we
			// keep a palette slug and a custom hex so switching modes does
			// not lose the other mode's choice. Custom seeds with the
			// fallback hex so the picker opens on something, not black.
			$defaults[ "color_{$key}_palette" ] = '';
			$defaults[ "color_{$key}_custom" ]  = $meta[1];
			$defaults[ "color_{$key}_opacity" ] = 100;
		}

		$defaults['color_source'] = 'follow';

		return $defaults;
	}

	public static function get() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	public static function excluded_ids() {
		$settings = self::get();
		if ( empty( $settings['exclude_ids'] ) ) {
			return array();
		}
		$parts = array_map( 'trim', explode( ',', $settings['exclude_ids'] ) );
		return array_filter( array_map( 'intval', $parts ) );
	}

	/**
	 * Whether a post is excluded, translations included.
	 *
	 * On a multilingual site a post and each of its translations are
	 * separate posts with separate IDs, so a bare ID list would exclude one
	 * language and miss the rest. The current post and every excluded ID are
	 * normalised to the default language and compared, so excluding any one
	 * translation excludes the whole group, whichever language's ID was
	 * typed into the field.
	 *
	 * Both WPML and Polylang implement the wpml_object_id and
	 * wpml_default_language filters. With neither active, those filters are
	 * absent: wpml_default_language returns null, and the direct match above
	 * is the whole story, so single-language sites pay nothing.
	 */
	public static function is_excluded( $post_id ) {
		$ids = self::excluded_ids();
		if ( empty( $ids ) ) {
			return false;
		}

		$post_id = (int) $post_id;

		if ( in_array( $post_id, $ids, true ) ) {
			return true;
		}

		// wpml_default_language and wpml_object_id are WPML's and Polylang's
		// own filters, called here to read translation data, not declared by
		// this plugin, so the prefix rule does not apply.
		$default_lang = apply_filters( 'wpml_default_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party (WPML/Polylang) filter.
		if ( empty( $default_lang ) ) {
			return false;
		}

		$current = (int) apply_filters( 'wpml_object_id', $post_id, 'post', true, $default_lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party (WPML/Polylang) filter.

		foreach ( $ids as $eid ) {
			$normalised = (int) apply_filters( 'wpml_object_id', (int) $eid, 'post', true, $default_lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party (WPML/Polylang) filter.
			if ( $normalised === $current ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Heading levels as an array of ints, e.g. array( 2, 3 ), for
	 * RPTOC_Render::process_content().
	 */
	public static function heading_level_ints() {
		$settings = self::get();
		$levels   = array();
		foreach ( (array) $settings['heading_levels'] as $lvl ) {
			$levels[] = (int) str_replace( 'h', '', $lvl );
		}
		return ! empty( $levels ) ? $levels : array( 2, 3 );
	}

	/**
	 * Class name whose headings are skipped entirely: a themed "Related
	 * posts" or "Comments" heading that shouldn't become a section.
	 */
	public static function excluded_class() {
		$settings = self::get();
		return isset( $settings['exclude_class'] ) ? trim( $settings['exclude_class'] ) : '';
	}

	/**
	 * Whether to emit the ItemList structured data. On by default.
	 */
	public static function schema_enabled() {
		$settings = self::get();
		return ! empty( $settings['schema_enabled'] );
	}

	/**
	 * The custom content-area selector, or an empty string when automatic
	 * detection is in effect. Held even while automatic is selected, so a
	 * switch back does not lose the typed value; only surfaced to the
	 * frontend when the mode actually calls for it.
	 */
	public static function content_selector() {
		$settings = self::get();
		if ( 'selector' !== $settings['content_detect'] ) {
			return '';
		}
		return isset( $settings['content_selector'] ) ? trim( $settings['content_selector'] ) : '';
	}

	/**
	 * Header-offset configuration for the frontend: whether a fixed offset
	 * is in force and its pixel value. In automatic mode the value is null
	 * and the script measures the detected header instead.
	 */
	public static function header_offset_config() {
		$settings = self::get();
		$custom   = ( 'custom' === $settings['header_offset_mode'] );
		return array(
			'mode'  => $custom ? 'custom' : 'auto',
			'value' => $custom ? (int) $settings['header_offset'] : null,
		);
	}

	/* ------------------------------------------------------------------ */
	/* Colour sources                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * The editor palette as WordPress itself resolves it: core defaults,
	 * overridden by the theme's theme.json, overridden by anything set in
	 * the global styles UI. Any theme shipping a theme.json is picked up
	 * here, which is every block theme and a growing number of classic
	 * ones, so this is the theme-agnostic tier.
	 *
	 * @return array slug => label
	 */
	public static function theme_palette() {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return array();
		}

		$palette = wp_get_global_settings( array( 'color', 'palette' ) );
		$out     = array();

		foreach ( array( 'theme', 'custom', 'default' ) as $origin ) {
			if ( empty( $palette[ $origin ] ) || ! is_array( $palette[ $origin ] ) ) {
				continue;
			}
			foreach ( $palette[ $origin ] as $entry ) {
				if ( empty( $entry['slug'] ) ) {
					continue;
				}
				$slug = sanitize_key( $entry['slug'] );
				if ( '' === $slug || isset( $out[ $slug ] ) ) {
					continue;
				}
				$out[ $slug ] = ! empty( $entry['name'] ) ? $entry['name'] : $slug;
			}
		}

		return $out;
	}

	private static function sanitize_hex( $value ) {
		$hex = sanitize_hex_color( $value );
		return $hex ? $hex : '';
	}

	/**
	 * A conservative pass over a user-entered CSS selector. This is a
	 * gatekeeper, not a parser: it strips characters that have no business
	 * in a selector (angle brackets, braces, semicolons, quotes) so nothing
	 * odd reaches the DOM, then trims. Real validity is settled on the
	 * frontend, where the selector is handed to querySelector inside a
	 * try/catch and detection falls back to automatic if it throws or
	 * finds nothing. Server-side we cannot know the theme's markup, so we
	 * deliberately do not reject on structure.
	 */
	private static function sanitize_selector( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		// Allow the characters a normal content selector needs: names,
		// combinators, attribute syntax, pseudo-classes, commas, spaces.
		$value = preg_replace( '/[^A-Za-z0-9 _\-.#>\[\]="\':(),~+*^$|]/', '', $value );
		return trim( (string) $value );
	}

	/**
	 * The CSS base value for one role under the current global source. In
	 * palette mode this is a theme.json preset variable; in custom mode a
	 * hex. Palette variables are referenced, never flattened, so a role
	 * pinned to a palette colour keeps following a theme's dark/light swap.
	 */
	private static function role_base( $source, $palette_slug, $custom_hex, $default_hex ) {
		if ( 'palette' === $source && '' !== $palette_slug ) {
			return 'var(--wp--preset--color--' . $palette_slug . ')';
		}
		if ( 'custom' === $source ) {
			return '' !== $custom_hex ? $custom_hex : $default_hex;
		}
		return $default_hex;
	}

	/**
	 * One CSS custom-property declaration per colour role. Emits nothing at
	 * all in "follow" mode: there the stylesheet's own fallback covers the
	 * first paint and the frontend script overwrites it with the theme's
	 * real rendered colours. In palette and custom modes every role is
	 * emitted, opacity applied with color-mix() against the chosen base so
	 * a palette variable keeps responding to a mode switch.
	 *
	 * @return array of "--var:value" strings.
	 */
	public static function color_declarations() {
		$settings = self::get();
		$source   = isset( $settings['color_source'] ) ? $settings['color_source'] : 'follow';

		if ( 'follow' === $source ) {
			return array();
		}

		$out = array();

		foreach ( self::COLOR_ROLES as $key => $meta ) {
			list( $var, $default_hex ) = $meta;

			$palette = isset( $settings[ "color_{$key}_palette" ] ) ? $settings[ "color_{$key}_palette" ] : '';
			$custom  = isset( $settings[ "color_{$key}_custom" ] ) ? $settings[ "color_{$key}_custom" ] : '';
			$opacity = isset( $settings[ "color_{$key}_opacity" ] ) ? (int) $settings[ "color_{$key}_opacity" ] : 100;
			$opacity = max( 0, min( 100, $opacity ) );

			$base = self::role_base( $source, $palette, $custom, $default_hex );

			if ( $opacity <= 0 ) {
				$value = 'transparent';
			} elseif ( $opacity >= 100 ) {
				$value = $base;
			} else {
				$value = 'color-mix(in srgb, ' . $base . ' ' . $opacity . '%, transparent)';
			}

			$out[] = $var . ':' . $value;
		}

		return $out;
	}

	/**
	 * In "follow" mode every role is resolved from the theme's rendered
	 * colours in the browser, so all four are handed to the frontend
	 * script. In palette and custom modes nothing is auto, so the list is
	 * empty and the script leaves the declared values alone.
	 */
	public static function auto_roles() {
		$settings = self::get();
		$source   = isset( $settings['color_source'] ) ? $settings['color_source'] : 'follow';

		if ( 'follow' !== $source ) {
			return array();
		}

		$out = array();
		foreach ( self::COLOR_ROLES as $key => $meta ) {
			$out[ $key ] = array(
				'var'     => $meta[0],
				'opacity' => 100,
			);
		}

		return $out;
	}

	/**
	 * Font-size and padding overrides. Font size is only emitted under
	 * 'custom'; in the default 'inherit' mode nothing is emitted and the
	 * stylesheet's own font-size: inherit pulls the theme's body copy
	 * size straight through.
	 *
	 * @return array of "--var:value" strings.
	 */
	public static function typography_declarations() {
		$settings = self::get();
		$out      = array();

		if ( 'custom' === $settings['font_size_mode'] ) {
			$out[] = '--rptoc-font-size:' . (int) $settings['font_size_desktop'] . 'px';
			$out[] = '--rptoc-font-size-mobile:' . (int) $settings['font_size_mobile'] . 'px';
		}

		$out[] = '--rptoc-pad-y:' . (int) $settings['pad_y'] . 'px';
		$out[] = '--rptoc-pad-x:' . (int) $settings['pad_x'] . 'px';
		$out[] = '--rptoc-pad-y-mobile:' . (int) $settings['pad_y_mobile'] . 'px';
		$out[] = '--rptoc-pad-x-mobile:' . (int) $settings['pad_x_mobile'] . 'px';

		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Admin plumbing                                                      */
	/* ------------------------------------------------------------------ */

	public function register_menu() {
		add_options_page(
			__( 'Erryn Reading Progress & Table of Contents', 'erryn-reading-progress-table-of-contents' ),
			__( 'Reading Progress', 'erryn-reading-progress-table-of-contents' ),
			'manage_options',
			'rptoc-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( 'rptoc_settings_group', self::OPTION, array( $this, 'sanitize' ) );
	}

	/**
	 * Admin assets, only on our own screen. Still no build step: one
	 * stylesheet, one vanilla script, no bundler.
	 */
	public function enqueue_admin( $hook ) {
		if ( 'settings_page_rptoc-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style( 'rptoc-admin', RPTOC_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), RPTOC_VERSION );
		wp_enqueue_script( 'rptoc-admin', RPTOC_URL . 'assets/js/admin.js', array( 'wp-color-picker' ), RPTOC_VERSION, true );
	}

	/**
	 * Keeps a submitted palette slug only if the active theme actually
	 * offers it, so a stale slug from a since-removed palette entry cannot
	 * resolve to a dangling CSS variable.
	 */
	private function valid_palette_slug( $value ) {
		$slug = sanitize_key( (string) $value );
		if ( '' === $slug ) {
			return '';
		}
		$palette = self::theme_palette();
		return isset( $palette[ $slug ] ) ? $slug : '';
	}

	public function sanitize( $input ) {
		$out = self::defaults();

		$out['enabled']      = ! empty( $input['enabled'] ) ? 1 : 0;
		$out['min_headings'] = isset( $input['min_headings'] ) ? max( 1, min( 12, (int) $input['min_headings'] ) ) : 3;

		$post_types = array();
		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $pt ) {
				$post_types[] = sanitize_key( $pt );
			}
		}
		$out['post_types'] = ! empty( $post_types ) ? $post_types : array( 'post', 'page' );

		$out['exclude_ids'] = isset( $input['exclude_ids'] ) ? sanitize_text_field( $input['exclude_ids'] ) : '';

		$levels = array();
		if ( isset( $input['heading_levels'] ) && is_array( $input['heading_levels'] ) ) {
			foreach ( self::HEADING_LEVELS as $lvl ) {
				if ( ! empty( $input['heading_levels'][ $lvl ] ) ) {
					$levels[] = $lvl;
				}
			}
		}
		$out['heading_levels'] = ! empty( $levels ) ? $levels : array( 'h2', 'h3' );

		// A bare class name, not a selector: it's matched against the
		// heading's own class attribute during the render pass, so a
		// leading dot or a descendant selector would never match.
		$out['exclude_class'] = isset( $input['exclude_class'] )
			? trim( preg_replace( '/[^A-Za-z0-9_\-]/', '', $input['exclude_class'] ) )
			: '';

		$out['style']             = ( isset( $input['style'] ) && 'text' === $input['style'] ) ? 'text' : 'pills';
		$out['align']             = ( isset( $input['align'] ) && in_array( $input['align'], array( 'left', 'center', 'right' ), true ) ) ? $input['align'] : 'center';
		$out['layout']            = ( isset( $input['layout'] ) && 'rail' === $input['layout'] ) ? 'rail' : 'bar';
		$out['rail_side']         = ( isset( $input['rail_side'] ) && 'left' === $input['rail_side'] ) ? 'left' : 'right';
		$out['progress_position'] = ( isset( $input['progress_position'] ) && 'below' === $input['progress_position'] ) ? 'below' : 'above';
		$out['dock']              = ( isset( $input['dock'] ) && 'header' === $input['dock'] ) ? 'header' : 'bottom';
		$out['header_selector']   = isset( $input['header_selector'] ) ? sanitize_text_field( $input['header_selector'] ) : '';

		$out['header_offset_mode'] = ( isset( $input['header_offset_mode'] ) && 'custom' === $input['header_offset_mode'] ) ? 'custom' : 'auto';
		$out['header_offset']      = isset( $input['header_offset'] ) ? max( 0, min( 400, (int) $input['header_offset'] ) ) : 0;

		$out['content_detect']   = ( isset( $input['content_detect'] ) && 'selector' === $input['content_detect'] ) ? 'selector' : 'auto';
		$out['content_selector'] = isset( $input['content_selector'] ) ? self::sanitize_selector( $input['content_selector'] ) : '';

		$out['schema_enabled'] = ! empty( $input['schema_enabled'] ) ? 1 : 0;
		$out['shadow']         = ! empty( $input['shadow'] ) ? 1 : 0;

		$out['font_size_mode']    = ( isset( $input['font_size_mode'] ) && 'custom' === $input['font_size_mode'] ) ? 'custom' : 'inherit';
		$out['font_size_desktop'] = isset( $input['font_size_desktop'] ) ? max( 10, min( 28, (int) $input['font_size_desktop'] ) ) : 15;
		$out['font_size_mobile']  = isset( $input['font_size_mobile'] ) ? max( 10, min( 28, (int) $input['font_size_mobile'] ) ) : 14;

		$out['pad_y']        = isset( $input['pad_y'] ) ? max( 2, min( 24, (int) $input['pad_y'] ) ) : 6;
		$out['pad_x']        = isset( $input['pad_x'] ) ? max( 4, min( 36, (int) $input['pad_x'] ) ) : 12;
		$out['pad_y_mobile'] = isset( $input['pad_y_mobile'] ) ? max( 2, min( 24, (int) $input['pad_y_mobile'] ) ) : 8;
		$out['pad_x_mobile'] = isset( $input['pad_x_mobile'] ) ? max( 4, min( 36, (int) $input['pad_x_mobile'] ) ) : 14;

		$source = ( isset( $input['color_source'] ) && in_array( $input['color_source'], array( 'follow', 'palette', 'custom' ), true ) )
			? $input['color_source']
			: 'follow';
		$out['color_source'] = $source;

		foreach ( self::COLOR_ROLES as $key => $meta ) {
			$out[ "color_{$key}_palette" ] = isset( $input[ "color_{$key}_palette" ] ) ? $this->valid_palette_slug( $input[ "color_{$key}_palette" ] ) : '';

			$hex = isset( $input[ "color_{$key}_custom" ] ) ? self::sanitize_hex( $input[ "color_{$key}_custom" ] ) : '';
			$out[ "color_{$key}_custom" ] = '' !== $hex ? $hex : $meta[1];

			$out[ "color_{$key}_opacity" ] = isset( $input[ "color_{$key}_opacity" ] ) ? max( 0, min( 100, (int) $input[ "color_{$key}_opacity" ] ) ) : 100;
		}

		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Field partials                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * A range input and a number input bound to the same value. The
	 * pairing happens in admin.js; either one drives the other. The
	 * number field is the one that submits and the one screen readers
	 * see, so the range is marked decorative and taken out of the tab
	 * order rather than duplicating the control for keyboard users.
	 */
	private function render_slider( $key, $label, $value, $min, $max, $unit = 'px' ) {
		$name = self::OPTION . '[' . $key . ']';
		$id   = 'rptoc-' . str_replace( '_', '-', $key );
		?>
		<div class="rptoc-slider">
			<label class="rptoc-slider__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input
				type="range"
				class="rptoc-slider__range"
				min="<?php echo esc_attr( $min ); ?>"
				max="<?php echo esc_attr( $max ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				aria-hidden="true"
				tabindex="-1"
				data-rptoc-pair="<?php echo esc_attr( $id ); ?>" />
			<span class="rptoc-slider__value">
				<input
					type="number"
					id="<?php echo esc_attr( $id ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					min="<?php echo esc_attr( $min ); ?>"
					max="<?php echo esc_attr( $max ); ?>"
					class="small-text" />
				<?php if ( '' !== $unit ) : ?>
					<span class="rptoc-slider__unit"><?php echo esc_html( $unit ); ?></span>
				<?php endif; ?>
			</span>
		</div>
		<?php
	}

	/**
	 * One colour role. Both a palette dropdown and a custom colour input
	 * are rendered; the Colours panel's mode decides which is shown, in CSS
	 * driven by admin.js, so switching source keeps the other mode's value.
	 * Opacity is shared by both. A contrast note may be attached beneath by
	 * admin.js when this role is one half of a checked pair.
	 */
	private function render_color_row( $settings, $key, $label, $hint = '' ) {
		$palette_field = self::OPTION . "[color_{$key}_palette]";
		$custom_field  = self::OPTION . "[color_{$key}_custom]";
		$opacity_field = self::OPTION . "[color_{$key}_opacity]";
		$opacity_id    = 'rptoc-opacity-' . str_replace( '_', '-', $key );
		$roles         = self::COLOR_ROLES;
		$default_hex   = $roles[ $key ][1];
		$palette_slug  = $settings[ "color_{$key}_palette" ];
		$theme_palette = self::theme_palette();
		?>
		<div class="rptoc-color" data-rptoc-color-row data-rptoc-role="<?php echo esc_attr( $key ); ?>">
			<div class="rptoc-color__label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( '' !== $hint ) : ?>
					<span class="rptoc-color__hint"><?php echo esc_html( $hint ); ?></span>
				<?php endif; ?>
			</div>

			<div class="rptoc-color__palette">
				<select name="<?php echo esc_attr( $palette_field ); ?>">
					<option value="" <?php selected( $palette_slug, '' ); ?>><?php esc_html_e( 'Plugin default', 'erryn-reading-progress-table-of-contents' ); ?></option>
					<?php foreach ( $theme_palette as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $palette_slug, $slug ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="rptoc-color__custom">
				<input type="text" class="rptoc-color-field" name="<?php echo esc_attr( $custom_field ); ?>" value="<?php echo esc_attr( $settings[ "color_{$key}_custom" ] ); ?>" data-default-color="<?php echo esc_attr( $default_hex ); ?>" data-rptoc-hex />
			</div>

			<div class="rptoc-color__opacity">
				<label class="rptoc-color__opacity-label" for="<?php echo esc_attr( $opacity_id ); ?>"><?php esc_html_e( 'Opacity', 'erryn-reading-progress-table-of-contents' ); ?></label>
				<input type="range" class="rptoc-slider__range" min="0" max="100" value="<?php echo esc_attr( $settings[ "color_{$key}_opacity" ] ); ?>" aria-hidden="true" tabindex="-1" data-rptoc-pair="<?php echo esc_attr( $opacity_id ); ?>" />
				<input type="number" id="<?php echo esc_attr( $opacity_id ); ?>" name="<?php echo esc_attr( $opacity_field ); ?>" value="<?php echo esc_attr( $settings[ "color_{$key}_opacity" ] ); ?>" min="0" max="100" class="small-text" />
				<span class="rptoc-slider__unit">%</span>
			</div>

			<p class="rptoc-color__contrast" data-rptoc-contrast="<?php echo esc_attr( $key ); ?>" hidden></p>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Page                                                                */
	/* ------------------------------------------------------------------ */

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings   = self::get();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div class="wrap rptoc-admin">

			<div class="rptoc-masthead">
				<div class="rptoc-masthead__bar">
					<h1 class="rptoc-masthead__title"><?php esc_html_e( 'Erryn Reading Progress & Table of Contents', 'erryn-reading-progress-table-of-contents' ); ?></h1>
					<span class="rptoc-masthead__brand" aria-hidden="true"><?php echo self::brand_icon(); // phpcs:ignore WordPress.Security.EscapeOutput -- static, self-contained SVG literal. ?></span>
				</div>
				<div class="rptoc-masthead__sub">
					<?php esc_html_e( 'A lightweight progress indicator and navigable table of contents for WordPress.', 'erryn-reading-progress-table-of-contents' ); ?>
				</div>
			</div>

			<form method="post" action="options.php" class="rptoc-shell">
				<?php settings_fields( 'rptoc_settings_group' ); ?>

				<div class="rptoc-tabs" role="tablist" data-rptoc-tabs>
					<button type="button" class="rptoc-tabs__tab is-active" role="tab" aria-selected="true" data-rptoc-tab="general"><?php esc_html_e( 'General', 'erryn-reading-progress-table-of-contents' ); ?></button>
					<button type="button" class="rptoc-tabs__tab" role="tab" aria-selected="false" data-rptoc-tab="placement"><?php esc_html_e( 'Placement', 'erryn-reading-progress-table-of-contents' ); ?></button>
					<button type="button" class="rptoc-tabs__tab" role="tab" aria-selected="false" data-rptoc-tab="style"><?php esc_html_e( 'Appearance', 'erryn-reading-progress-table-of-contents' ); ?></button>
					<button type="button" class="rptoc-tabs__tab" role="tab" aria-selected="false" data-rptoc-tab="colours"><?php esc_html_e( 'Colours', 'erryn-reading-progress-table-of-contents' ); ?></button>
				</div>

				<div class="rptoc-shell__body">

				<section class="rptoc-panel is-active" data-rptoc-panel="general">

					<div class="rptoc-card">
						<h2><?php esc_html_e( 'When it appears', 'erryn-reading-progress-table-of-contents' ); ?></h2>

						<div class="rptoc-field">
							<label class="rptoc-check">
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
								<span><?php esc_html_e( 'Add it automatically when a page has enough headings', 'erryn-reading-progress-table-of-contents' ); ?></span>
							</label>
							<p class="description"><?php esc_html_e( 'A page using the TOC Anchor block, or the manual block, always renders regardless of this setting.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>

						<div class="rptoc-field">
							<?php $this->render_slider( 'min_headings', __( 'Minimum sections', 'erryn-reading-progress-table-of-contents' ), $settings['min_headings'], 1, 12, '' ); ?>
							<p class="description"><?php esc_html_e( 'Below this count, auto-insert leaves the page alone. A three-heading article gets a bar worth having. A two-heading one rarely does.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'Post types', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-checks">
								<?php foreach ( $post_types as $pt ) : ?>
									<label class="rptoc-check">
										<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, (array) $settings['post_types'], true ) ); ?> />
										<span><?php echo esc_html( $pt->labels->name ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="rptoc-field">
							<label class="rptoc-field__label" for="rptoc-exclude-ids"><?php esc_html_e( 'Never show on these post IDs', 'erryn-reading-progress-table-of-contents' ); ?></label>
							<input type="text" id="rptoc-exclude-ids" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[exclude_ids]" value="<?php echo esc_attr( $settings['exclude_ids'] ); ?>" placeholder="12, 48, 301" />
							<p class="description"><?php esc_html_e( 'Comma separated. On a multilingual site with WPML or Polylang, excluding any one translation excludes the whole group, so a single ID covers every language.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>
					</div>

					<div class="rptoc-card">
						<h2><?php esc_html_e( 'What counts as a section', 'erryn-reading-progress-table-of-contents' ); ?></h2>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'Heading levels', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-checks rptoc-checks--inline">
								<?php foreach ( self::HEADING_LEVELS as $lvl ) : ?>
									<label class="rptoc-check rptoc-check--chip">
										<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[heading_levels][<?php echo esc_attr( $lvl ); ?>]" value="1" <?php checked( in_array( $lvl, (array) $settings['heading_levels'], true ) ); ?> />
										<span><?php echo esc_html( strtoupper( $lvl ) ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="description"><?php esc_html_e( 'Each level nests under whichever selected level sits above it. H5 and H6 earn their place on a long reference page; on a normal article they mostly make the strip noisy.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>

						<div class="rptoc-field">
							<label class="rptoc-field__label" for="rptoc-exclude-class"><?php esc_html_e( 'Skip headings with this class', 'erryn-reading-progress-table-of-contents' ); ?></label>
							<input type="text" id="rptoc-exclude-class" class="regular-text code" name="<?php echo esc_attr( self::OPTION ); ?>[exclude_class]" value="<?php echo esc_attr( $settings['exclude_class'] ); ?>" placeholder="no-toc" />
							<p class="description">
								<?php esc_html_e( 'A single class name, no leading dot. Any heading carrying it is skipped: useful for a themed "Related posts" or "Comments" heading that would otherwise sit at the end of every table of contents on the site.', 'erryn-reading-progress-table-of-contents' ); ?>
							</p>
							<p class="description">
								<?php esc_html_e( 'To add a section that is not a heading at all, use the TOC Anchor block. Placing any anchor block on a page switches that page off heading detection entirely.', 'erryn-reading-progress-table-of-contents' ); ?>
							</p>
						</div>

						<div class="rptoc-field">
							<label class="rptoc-check">
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[schema_enabled]" value="1" <?php checked( $settings['schema_enabled'], 1 ); ?> />
								<span><?php esc_html_e( 'Add Table of Contents structured data where supported', 'erryn-reading-progress-table-of-contents' ); ?></span>
							</label>
							<p class="description"><?php esc_html_e( 'Uses the detected sections to describe the page structure. No visible markup is added beyond the table of contents itself.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>

						<details class="rptoc-advanced">
							<summary class="rptoc-advanced__summary"><?php esc_html_e( 'Advanced detection', 'erryn-reading-progress-table-of-contents' ); ?></summary>

							<div class="rptoc-field">
								<span class="rptoc-field__label"><?php esc_html_e( 'Content area', 'erryn-reading-progress-table-of-contents' ); ?></span>
								<div class="rptoc-segmented">
									<label class="rptoc-check">
										<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[content_detect]" value="auto" <?php checked( $settings['content_detect'], 'auto' ); ?> data-rptoc-toggle="content-selector" data-rptoc-toggle-when="off" />
										<span><?php esc_html_e( 'Detect automatically', 'erryn-reading-progress-table-of-contents' ); ?></span>
									</label>
									<label class="rptoc-check">
										<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[content_detect]" value="selector" <?php checked( $settings['content_detect'], 'selector' ); ?> data-rptoc-toggle="content-selector" data-rptoc-toggle-when="on" />
										<span><?php esc_html_e( 'CSS selector', 'erryn-reading-progress-table-of-contents' ); ?></span>
									</label>
								</div>
							</div>

							<div class="rptoc-field rptoc-field--dependent" data-rptoc-dependent="content-selector">
								<label class="rptoc-field__label" for="rptoc-content-selector"><?php esc_html_e( 'Content CSS selector', 'erryn-reading-progress-table-of-contents' ); ?></label>
								<input type="text" id="rptoc-content-selector" class="regular-text code" name="<?php echo esc_attr( self::OPTION ); ?>[content_selector]" value="<?php echo esc_attr( $settings['content_selector'] ); ?>" placeholder="article .entry-content" />
								<p class="description"><?php esc_html_e( 'Enter the selector for the element containing the article content. Use this only when automatic detection does not work correctly with the active theme. If it matches nothing on the page, detection falls back to automatic.', 'erryn-reading-progress-table-of-contents' ); ?></p>
							</div>
						</details>
					</div>
				</section>

				<section class="rptoc-panel" data-rptoc-panel="placement">
					<div class="rptoc-card">
						<h2><?php esc_html_e( 'Layout', 'erryn-reading-progress-table-of-contents' ); ?></h2>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'How it shows', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[layout]" value="bar" <?php checked( $settings['layout'], 'bar' ); ?> data-rptoc-toggle="layout-rail" data-rptoc-toggle-when="off" />
									<span><?php esc_html_e( 'Horizontal bar', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[layout]" value="rail" <?php checked( $settings['layout'], 'rail' ); ?> data-rptoc-toggle="layout-rail" data-rptoc-toggle-when="on" />
									<span><?php esc_html_e( 'Side rail', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
							</div>
							<p class="description"><?php esc_html_e( 'Horizontal bar: a full-width progress bar with the section links displayed alongside it. Side rail: a compact vertical marker fixed to the edge of the viewport that expands to reveal the section list.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>

						<div class="rptoc-field rptoc-field--dependent" data-rptoc-dependent="layout-rail">
							<span class="rptoc-field__label"><?php esc_html_e( 'Rail side', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[rail_side]" value="right" <?php checked( $settings['rail_side'], 'right' ); ?> />
									<span><?php esc_html_e( 'Right', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[rail_side]" value="left" <?php checked( $settings['rail_side'], 'left' ); ?> />
									<span><?php esc_html_e( 'Left', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
							</div>
							<p class="description"><?php esc_html_e( 'On narrow screens the rail cannot work on touch, so it falls back to the bar automatically. The dock, alignment and progress-bar settings below govern how that fallback looks.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>
					</div>

					<div class="rptoc-card">
						<h2><?php esc_html_e( 'Where it sits', 'erryn-reading-progress-table-of-contents' ); ?></h2>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'Dock position', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[dock]" value="bottom" <?php checked( $settings['dock'], 'bottom' ); ?> data-rptoc-toggle="dock-header" data-rptoc-toggle-when="off" />
									<span><?php esc_html_e( 'Bottom of the viewport', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[dock]" value="header" <?php checked( $settings['dock'], 'header' ); ?> data-rptoc-toggle="dock-header" data-rptoc-toggle-when="on" />
									<span><?php esc_html_e( 'Directly below the header', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
							</div>
						</div>

						<div class="rptoc-field rptoc-field--dependent" data-rptoc-dependent="dock-header">
							<label class="rptoc-field__label" for="rptoc-header-selector"><?php esc_html_e( 'Header selector', 'erryn-reading-progress-table-of-contents' ); ?></label>
							<input type="text" id="rptoc-header-selector" class="regular-text code" name="<?php echo esc_attr( self::OPTION ); ?>[header_selector]" value="<?php echo esc_attr( $settings['header_selector'] ); ?>" placeholder="#masthead" />
							<p class="description"><?php esc_html_e( 'Leave blank to detect it automatically: the plugin looks for a header landmark the browser reports as fixed or sticky, rather than guessing at class names. Fill this in only if that picks the wrong element.', 'erryn-reading-progress-table-of-contents' ); ?></p>
							<p class="description"><?php esc_html_e( 'This mode needs a header that stays on screen. If yours scrolls away with the page, a gap opens above the bar, and the browser console will tell you so.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>

						<div class="rptoc-field rptoc-field--dependent" data-rptoc-dependent="dock-header">
							<span class="rptoc-field__label"><?php esc_html_e( 'Header offset', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[header_offset_mode]" value="auto" <?php checked( $settings['header_offset_mode'], 'auto' ); ?> data-rptoc-toggle="header-offset" data-rptoc-toggle-when="off" />
									<span><?php esc_html_e( 'Auto', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[header_offset_mode]" value="custom" <?php checked( $settings['header_offset_mode'], 'custom' ); ?> data-rptoc-toggle="header-offset" data-rptoc-toggle-when="on" />
									<span><?php esc_html_e( 'Custom', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
							</div>
							<div class="rptoc-field rptoc-field--dependent" data-rptoc-dependent="header-offset">
								<?php $this->render_slider( 'header_offset', __( 'Custom offset', 'erryn-reading-progress-table-of-contents' ), $settings['header_offset'], 0, 400 ); ?>
								<p class="description"><?php esc_html_e( 'Set the distance from the top of the viewport for when the site header cannot be detected automatically. Auto measures the detected header and still accounts for the admin bar.', 'erryn-reading-progress-table-of-contents' ); ?></p>
							</div>
						</div>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'Progress bar', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[progress_position]" value="above" <?php checked( $settings['progress_position'], 'above' ); ?> />
									<span><?php esc_html_e( 'Above the links', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[progress_position]" value="below" <?php checked( $settings['progress_position'], 'below' ); ?> />
									<span><?php esc_html_e( 'Below the links', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
							</div>
						</div>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'Alignment', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<?php
								$aligns = array(
									'left'   => __( 'Start', 'erryn-reading-progress-table-of-contents' ),
									'center' => __( 'Centre', 'erryn-reading-progress-table-of-contents' ),
									'right'  => __( 'End', 'erryn-reading-progress-table-of-contents' ),
								);
								foreach ( $aligns as $val => $label ) :
									?>
									<label class="rptoc-check">
										<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[align]" value="<?php echo esc_attr( $val ); ?>" <?php checked( $settings['align'], $val ); ?> />
										<span><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="description"><?php esc_html_e( 'Only affects lists short enough to fit. Anything wider scrolls from the start. Start and end follow text direction, so they flip on right-to-left sites.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>
					</div>
				</section>

				<section class="rptoc-panel" data-rptoc-panel="style">
					<div class="rptoc-card">
						<h2><?php esc_html_e( 'Links and text', 'erryn-reading-progress-table-of-contents' ); ?></h2>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'Link style', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[style]" value="pills" <?php checked( $settings['style'], 'pills' ); ?> data-rptoc-toggle="pill-padding" data-rptoc-toggle-when="on" />
									<span><?php esc_html_e( 'Pills', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[style]" value="text" <?php checked( $settings['style'], 'text' ); ?> data-rptoc-toggle="pill-padding" data-rptoc-toggle-when="off" />
									<span><?php esc_html_e( 'Plain text', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
							</div>
						</div>

						<div class="rptoc-field">
							<span class="rptoc-field__label"><?php esc_html_e( 'Font size', 'erryn-reading-progress-table-of-contents' ); ?></span>
							<div class="rptoc-segmented">
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[font_size_mode]" value="inherit" <?php checked( $settings['font_size_mode'], 'inherit' ); ?> data-rptoc-toggle="font-custom" data-rptoc-toggle-when="off" />
									<span><?php esc_html_e( 'Inherit from the theme', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
								<label class="rptoc-check">
									<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[font_size_mode]" value="custom" <?php checked( $settings['font_size_mode'], 'custom' ); ?> data-rptoc-toggle="font-custom" data-rptoc-toggle-when="on" />
									<span><?php esc_html_e( 'Set it myself', 'erryn-reading-progress-table-of-contents' ); ?></span>
								</label>
							</div>
						</div>

						<div class="rptoc-field rptoc-field--dependent" data-rptoc-dependent="font-custom">
							<?php
							$this->render_slider( 'font_size_desktop', __( 'Desktop', 'erryn-reading-progress-table-of-contents' ), $settings['font_size_desktop'], 10, 28 );
							$this->render_slider( 'font_size_mobile', __( 'Mobile', 'erryn-reading-progress-table-of-contents' ), $settings['font_size_mobile'], 10, 28 );
							?>
							<p class="description"><?php esc_html_e( 'Nested levels scale proportionally from whichever size is active.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>

						<div class="rptoc-field">
							<label class="rptoc-check">
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[shadow]" value="1" <?php checked( $settings['shadow'], 1 ); ?> />
								<span><?php esc_html_e( 'Drop shadow', 'erryn-reading-progress-table-of-contents' ); ?></span>
							</label>
							<p class="description"><?php esc_html_e( 'Off by default. A drop shadow reads well on a plain light page, but tends to muddy a dark or busy background, so it is left off unless you want it.', 'erryn-reading-progress-table-of-contents' ); ?></p>
						</div>
					</div>

					<div class="rptoc-card rptoc-card--dependent" data-rptoc-dependent="pill-padding">
						<h2><?php esc_html_e( 'Pill padding', 'erryn-reading-progress-table-of-contents' ); ?></h2>
						<div class="rptoc-columns">
							<div class="rptoc-columns__col">
								<h3><?php esc_html_e( 'Desktop', 'erryn-reading-progress-table-of-contents' ); ?></h3>
								<?php
								$this->render_slider( 'pad_y', __( 'Vertical', 'erryn-reading-progress-table-of-contents' ), $settings['pad_y'], 2, 24 );
								$this->render_slider( 'pad_x', __( 'Horizontal', 'erryn-reading-progress-table-of-contents' ), $settings['pad_x'], 4, 36 );
								?>
							</div>
							<div class="rptoc-columns__col">
								<h3><?php esc_html_e( 'Mobile', 'erryn-reading-progress-table-of-contents' ); ?></h3>
								<?php
								$this->render_slider( 'pad_y_mobile', __( 'Vertical', 'erryn-reading-progress-table-of-contents' ), $settings['pad_y_mobile'], 2, 24 );
								$this->render_slider( 'pad_x_mobile', __( 'Horizontal', 'erryn-reading-progress-table-of-contents' ), $settings['pad_x_mobile'], 4, 36 );
								?>
							</div>
						</div>
					</div>
				</section>

				<section class="rptoc-panel" data-rptoc-panel="colours">
					<div class="rptoc-card">
						<h2><?php esc_html_e( 'Colours', 'erryn-reading-progress-table-of-contents' ); ?></h2>

						<div class="rptoc-colors" data-rptoc-colors data-mode="<?php echo esc_attr( $settings['color_source'] ); ?>">
							<div class="rptoc-field">
								<span class="rptoc-field__label"><?php esc_html_e( 'Colour source', 'erryn-reading-progress-table-of-contents' ); ?></span>
								<div class="rptoc-segmented">
									<label class="rptoc-check">
										<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[color_source]" value="follow" <?php checked( $settings['color_source'], 'follow' ); ?> data-rptoc-color-mode />
										<span><?php esc_html_e( 'Follow rendered theme colours', 'erryn-reading-progress-table-of-contents' ); ?></span>
									</label>
									<label class="rptoc-check">
										<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[color_source]" value="palette" <?php checked( $settings['color_source'], 'palette' ); ?> data-rptoc-color-mode />
										<span><?php esc_html_e( 'Choose palette colours', 'erryn-reading-progress-table-of-contents' ); ?></span>
									</label>
									<label class="rptoc-check">
										<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[color_source]" value="custom" <?php checked( $settings['color_source'], 'custom' ); ?> data-rptoc-color-mode />
										<span><?php esc_html_e( 'Use custom colours', 'erryn-reading-progress-table-of-contents' ); ?></span>
									</label>
								</div>
								<p class="description rptoc-colors__note" data-rptoc-mode-note="follow"><?php esc_html_e( 'Uses the colours rendered by the active theme and follows supported light or dark mode changes automatically.', 'erryn-reading-progress-table-of-contents' ); ?></p>
								<p class="description rptoc-colors__note" data-rptoc-mode-note="palette"><?php esc_html_e( 'Pin each role to a colour from the active theme\'s palette. Palette colours are referenced as CSS variables, so they keep following a theme\'s light or dark switch.', 'erryn-reading-progress-table-of-contents' ); ?></p>
								<p class="description rptoc-colors__note" data-rptoc-mode-note="custom"><?php esc_html_e( 'Set an exact colour for each role. These are fixed values and do not follow theme mode changes.', 'erryn-reading-progress-table-of-contents' ); ?></p>
							</div>

							<?php if ( empty( self::theme_palette() ) ) : ?>
								<p class="description rptoc-colors__note" data-rptoc-mode-note="palette"><?php esc_html_e( 'The active theme ships no theme.json palette, so there are no palette colours to choose. Follow or custom still work.', 'erryn-reading-progress-table-of-contents' ); ?></p>
							<?php endif; ?>

							<div class="rptoc-color-roles" data-rptoc-color-roles>
								<?php
								$this->render_color_row( $settings, 'bg', __( 'Background', 'erryn-reading-progress-table-of-contents' ), __( 'The component background', 'erryn-reading-progress-table-of-contents' ) );
								$this->render_color_row( $settings, 'fg', __( 'Text', 'erryn-reading-progress-table-of-contents' ), __( 'Inactive links and supporting text', 'erryn-reading-progress-table-of-contents' ) );
								$this->render_color_row( $settings, 'accent', __( 'Accent', 'erryn-reading-progress-table-of-contents' ), __( 'The progress fill and active link', 'erryn-reading-progress-table-of-contents' ) );
								$this->render_color_row( $settings, 'accent_contrast', __( 'Accent contrast', 'erryn-reading-progress-table-of-contents' ), __( 'Text displayed over the active accent colour', 'erryn-reading-progress-table-of-contents' ) );
								?>
							</div>
						</div>
					</div>
				</section>


				</div><!-- .rptoc-shell__body -->

				<div class="rptoc-shell__actions">
					<?php submit_button( '', 'primary', 'submit', false ); ?>
				</div>
			</form>

			<p class="rptoc-admin__footer">
				<?php
				printf(
					/* translators: %s: erryn.io link. */
					esc_html__( 'Built by %s', 'erryn-reading-progress-table-of-contents' ),
					'<a href="https://erryn.io" target="_blank" rel="noopener noreferrer">erryn.io</a>'
				);
				?>
				<span class="rptoc-admin__version">&middot; <?php echo esc_html( 'v' . RPTOC_VERSION ); ?></span>
			</p>
		</div>
		<?php
	}

	/**
	 * The erryn.io mark, icon only, wordmark dropped. The fill keeps its
	 * own CSS variable so the colour stays with the brand rather than being
	 * hard-set here; the spin-on-load lives in admin.css. viewBox is the
	 * icon's own bounds once the source file's layer translate is applied.
	 */
	private static function brand_icon() {
		return '<svg class="rptoc-brand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38.4297 38.4297" width="40" height="40" role="img" aria-label="erryn.io" focusable="false">'
			. '<g transform="translate(-27.013682,-216.97152)">'
			. '<path fill="var(--erryn-icon-fill, #333333)" d="m 44.307199,216.97153 a 19.214818,19.214818 0 0 0 -17.293518,19.11873 19.214818,19.214818 0 0 0 17.293518,19.11873 v -3.86746 -9.60717 h -3.843176 v 8.60619 a 15.371854,15.371854 0 0 1 -9.486759,-12.32896 h 5.6441 v -3.84266 h -5.6441 a 15.371854,15.371854 0 0 1 9.486759,-12.32845 v 8.6062 h 3.843176 v -9.60769 z m 3.84266,0 v 3.86746 9.60769 h 3.843176 v -8.6062 a 15.371854,15.371854 0 0 1 9.607166,14.24978 15.371854,15.371854 0 0 1 -9.607166,14.25029 v -8.60619 h -3.843176 v 9.60717 3.86746 A 19.214818,19.214818 0 0 0 65.443377,236.09026 19.214818,19.214818 0 0 0 48.149859,216.97153 Z"></path>'
			. '</g></svg>';
	}
}
