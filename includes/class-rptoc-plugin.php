<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part ii of xi
 * ====================================================================
 *  The tavern was empty apart from several overturned chairs, a
 *  smouldering fireplace and an enormous rat wearing a leather
 *  waistcoat.
 *
 *    The rat raised a tiny crossbow. "Your money," it squeaked.
 *    Marf checked his pockets. "I have a button."
 *    "I'll take it."
 *
 *  The rat fired. Erryn drifted sideways and intercepted the bolt
 *  with his face. There was a metallic ping. He regarded the bolt
 *  now protruding from his forehead.
 *
 *    "This is broadly how the day has been going."
 *
 *  -> continues in includes/class-rptoc-settings.php
 * ====================================================================
 */

class RPTOC_Plugin {

	private $settings;
	private $block;
	private $anchor_block;

	public function __construct() {
		$this->settings     = new RPTOC_Settings();
		$this->block        = new RPTOC_Block();
		$this->anchor_block = new RPTOC_Anchor_Block();

		add_action( 'admin_menu', array( $this->settings, 'register_menu' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this->settings, 'enqueue_admin' ) );

		add_action(
			'init',
			function () {
				$this->block->register();
				$this->anchor_block->register();
			}
		);

		// Priority 8: clears the anchor registry right before this post's
		// blocks render, so a query loop never bleeds one post's anchors
		// into the next.
		add_filter( 'the_content', array( 'RPTOC_Anchor_Block', 'reset_filter' ), 8 );

		// Priority 30: after do_blocks (9), shortcodes (11) and wpautop (10),
		// so headings and anchor spans are already in the markup.
		add_filter( 'the_content', array( $this, 'filter_content' ), 30 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Shortcode placement, for the Classic Editor, page builders and
		// anywhere the block editor isn't. Both names point at the same
		// marker: it emits nothing itself, and its presence in the post
		// content is what tells filter_content to render, exactly like the
		// block. Position in the content is irrelevant, since the component
		// pins to the viewport wherever it is called.
		add_shortcode( 'rptoc_reading_progress_toc', '__return_empty_string' );
		add_shortcode( 'rptoc', '__return_empty_string' );

		// Per-post override: a small control in the editor to force the
		// component on, or off, for one post regardless of the global rules.
		add_action( 'init', array( $this, 'register_post_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
	}

	const META_KEY = '_rptoc_display';

	/**
	 * Registers the per-post display override. Kept out of REST and the
	 * custom-fields UI: it is edited only through the meta box below, so
	 * exposing it elsewhere would just invite a second, conflicting way to
	 * set it.
	 */
	public function register_post_meta() {
		register_post_meta(
			'',
			self::META_KEY,
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => false,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Adds the override control to the editor sidebar, on the post types the
	 * plugin is set to appear on plus posts and pages, so the "always show"
	 * option can reach a type auto-insert would otherwise skip.
	 */
	public function add_meta_box() {
		$settings = RPTOC_Settings::get();
		$types    = array_unique( array_merge( array( 'post', 'page' ), (array) $settings['post_types'] ) );

		foreach ( $types as $type ) {
			add_meta_box(
				'rptoc-display',
				__( 'Reading Progress & Contents', 'erryn-reading-progress-table-of-contents' ),
				array( $this, 'render_meta_box' ),
				$type,
				'side',
				'default'
			);
		}
	}

	public function render_meta_box( $post ) {
		$value = get_post_meta( $post->ID, self::META_KEY, true );
		wp_nonce_field( 'rptoc_display_meta', 'rptoc_display_nonce' );
		?>
		<p style="margin:0 0 8px;">
			<label for="rptoc-display-select"><?php esc_html_e( 'On this post:', 'erryn-reading-progress-table-of-contents' ); ?></label>
		</p>
		<select id="rptoc-display-select" name="rptoc_display" style="width:100%;">
			<option value="" <?php selected( $value, '' ); ?>><?php esc_html_e( 'Follow the settings', 'erryn-reading-progress-table-of-contents' ); ?></option>
			<option value="show" <?php selected( $value, 'show' ); ?>><?php esc_html_e( 'Always show', 'erryn-reading-progress-table-of-contents' ); ?></option>
			<option value="hide" <?php selected( $value, 'hide' ); ?>><?php esc_html_e( 'Never show', 'erryn-reading-progress-table-of-contents' ); ?></option>
		</select>
		<p class="description" style="margin-top:8px;"><?php esc_html_e( 'Overrides the global rules for this one post. "Always show" still needs a heading or two to build the list from.', 'erryn-reading-progress-table-of-contents' ); ?></p>
		<?php
	}

	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['rptoc_display_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['rptoc_display_nonce'] ) ), 'rptoc_display_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST['rptoc_display'] ) ? sanitize_key( wp_unslash( $_POST['rptoc_display'] ) ) : '';
		if ( ! in_array( $value, array( 'show', 'hide' ), true ) ) {
			$value = '';
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			update_post_meta( $post_id, self::META_KEY, $value );
		}
	}

	/**
	 * Adds heading ids (or reads the anchor registry) and, where warranted,
	 * appends the TOC/progress widget plus its JSON-LD, to the main post
	 * content. Runs once per request.
	 */
	public function filter_content( $content ) {
		static $done = false;

		if ( $done ) {
			return $content;
		}

		if ( is_admin() || is_feed() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post ) {
			return $content;
		}

		// Per-post override wins over everything global. "Never show" is the
		// firmest possible hide; "always show" even steps past an exclude-ID
		// entry, on the reasoning that the more specific choice is the one
		// the editor most recently made on purpose.
		$per_post = get_post_meta( $post->ID, self::META_KEY, true );

		if ( 'hide' === $per_post ) {
			return $content;
		}

		if ( 'show' !== $per_post && RPTOC_Settings::is_excluded( $post->ID ) ) {
			return $content;
		}

		$settings = RPTOC_Settings::get();
		$anchors  = RPTOC_Anchor_Block::get_registered();

		if ( ! empty( $anchors ) ) {
			// TOC Anchor blocks present: they replace H2/H3 detection
			// entirely, and their presence alone is reason enough to render.
			$sections_flat = $anchors;
			$nested        = RPTOC_Render::anchors_to_nested( $anchors );
			$content_out   = $content;
			$should_render = true;
		} else {
			$has_manual        = has_block( 'rptoc/toc-progress', $post )
				|| has_shortcode( $post->post_content, 'rptoc' )
				|| has_shortcode( $post->post_content, 'rptoc_reading_progress_toc' );
			$allowed_post_type = in_array( $post->post_type, (array) $settings['post_types'], true );

			$processed     = RPTOC_Render::process_content( $content, RPTOC_Settings::heading_level_ints(), RPTOC_Settings::excluded_class() );
			$sections_flat = $processed['headings'];
			$nested        = RPTOC_Render::nest( $sections_flat );
			$content_out   = $processed['content'];

			if ( 'show' === $per_post || $has_manual ) {
				$should_render = ! empty( $sections_flat );
			} elseif ( ! empty( $settings['enabled'] ) && $allowed_post_type ) {
				$should_render = count( $sections_flat ) >= (int) $settings['min_headings'];
			} else {
				$should_render = false;
			}
		}

		if ( ! $should_render ) {
			return $content;
		}

		$done = true;

		$widget = RPTOC_Render::widget_html(
			$nested,
			$sections_flat,
			get_permalink( $post ),
			$settings['style'],
			$settings['align'],
			$settings['progress_position'],
			$settings['dock'],
			$settings['layout'],
			$settings['rail_side'],
			! empty( $settings['schema_enabled'] ),
			! empty( $settings['shadow'] )
		);

		return $content_out . $widget;
	}

	/**
	 * Enqueued on every singular view; the script itself no-ops if
	 * [data-rptoc] isn't present, so this stays cheap on pages where
	 * the widget didn't render.
	 */
	public function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}

		wp_enqueue_style( 'rptoc', RPTOC_URL . 'assets/css/toc-progress.css', array(), RPTOC_VERSION );
		wp_enqueue_script( 'rptoc', RPTOC_URL . 'assets/js/toc-progress.js', array(), RPTOC_VERSION, true );

		$settings = RPTOC_Settings::get();

		wp_localize_script(
			'rptoc',
			'rptocSettings',
			array(
				'headerSelector' => $settings['header_selector'],
				'headerOffset'   => RPTOC_Settings::header_offset_config(),
				'contentSelector' => RPTOC_Settings::content_selector(),
				// Roles left on "match the theme". Resolved in the
				// browser from the page's own computed styles, because
				// the server has no idea what the theme actually renders
				// or which mode a dark/light toggle is currently in.
				'autoColors'     => RPTOC_Settings::auto_roles(),
			)
		);

		$decls = array_merge(
			RPTOC_Settings::color_declarations(),
			RPTOC_Settings::typography_declarations()
		);
		if ( ! empty( $decls ) ) {
			wp_add_inline_style( 'rptoc', ':root{' . implode( ';', $decls ) . ';}' );
		}
	}
}
